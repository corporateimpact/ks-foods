#!/usr/bin/env python abort_measure LINE alert
# coding=utf-8
"""
----- ケーエスフーズIoT測定値異常時のＬＩＮＥ通知 -----
"""
import time
import datetime
import smtplib
import requests
import mysql.connector


# 上限値項目のカラムと物理名を紐付けるdictonayを作成
item_dict = {
    "water_temp": "水温",
    "salinity": "塩分濃度",
    "do": "溶存酸素"
}

# メール送信のための情報を定数に格納する
SMTP_HOST = 'smtp.corporate.co.jp'
SMTP_PORT = 587
MAIL_USER_NAME = 'itou.shou@corporate.co.jp'
MAIL_PASSWORD = 'Y2g8tvNX'
MAIL_FROM_ADDRESS = 'itou.shou@corporate.co.jp'
# 送信先は複数になる。DBで受信者のマスタを作成して、送信先のON・OFFができるようにする
MAIL_TO_ADDRESS = 'itou.shou@corporate.co.jp'


def LINE_notify(str_message):
    """
    LINE Notifyの接続処理
    """

    url = "https://notify-api.line.me/api/notify"
#    token = #Here ACCESS-TOKEN input
    token = "ObFoG8pLNgIGpm04j7t0abp9wKMAJAuHHp08VFihIOb"  # <-TEST用のLINEトークン
    # token = "KuVOB4yhowvMFycKBKFayLmUD2U0F5SMvdB1bPJ4kPY"  # <--ケーエスフーズアラート用のLINEトークン
    headers = {"Authorization": "Bearer " + token}
    payload = {"message":  str_message}

    r = requests.post(url, headers=headers, params=payload)


def MESSAGE_SET(str_message):
    """
    LINEへ通知するメッセージを設定
    """

    if limit_tbl_item == "water_temp":
        str_message = str_message + "\n水温(" + format(current_value) + "℃)"
    elif limit_tbl_item == "salinity":
        str_message = str_message + "\n塩分濃度(" + format(current_value) + "%)"
    elif limit_tbl_item == "do":
        str_message = str_message + \
            "\n溶存酸素(" + format(current_value) + "mg/L)"
    else:
        pass
    return str_message


def set_mail_message():
    """
    送信するメールメッセージの設定を行う処理
    アラートごとの件名とメッセージを設定する
    """
    pass


def send_mail():
    """
    メール送信を行う処理
    """
    pass


# 10分前の時刻を取得
before_10min = time.time() - 600
before_10min = datetime.datetime.fromtimestamp(before_10min)

# LINE通知のメッセージタイトルを設定
line_message = "<< ケーエスフーズIoTアラート >>"
alert_flg = "OFF"  # LINEアラートが発生したら"ON"になる


# 測定値テーブルに接続し直近の測定値を取得
conn = mysql.connector.connect(
    user="root", password="pm#corporate1", host="localhost", database="ksfoods")
cur = conn.cursor()
cur.execute("SELECT * FROM data ORDER BY day DESC, time DESC LIMIT 1;")
for row in cur.fetchall():
    fact_id = row[0]  # 工場ID
    tank_no = row[1]  # 水槽ID
    day_tbl = row[2]  # 日付
    time_tbl = row[3]  # 時刻
    water_temp = row[4]  # 水温
    salinity = row[5]  # 塩分濃度
    do = row[6]  # 溶存酸素
cur.close()

# 測定値が直近のものか(10分前と比較)判断、測定が止まっていればアラート通知
daytime = format(day_tbl) + " " + format(time_tbl) + ".999999"
if format(daytime) > format(before_10min):
    # 最新の測定値なのでしきい値チェックを行う
    # しきい値テーブルからレコード取得
    cur = conn.cursor()
    cur2 = conn.cursor()
    cur.execute(
        "SELECT * FROM m_limit WHERE item IN ('water_temp','salinity','do');")
    for row in cur.fetchall():
        # テーブルの要素を変数に入れる
        limit_tbl_item = row[1]
        limit_tbl_max = row[2]
        limit_tbl_min = row[3]
        limit_tbl_flg = row[4]

        # 各項目で測定値をチェックする
        if limit_tbl_item == "water_temp":  # 水温
            current_value = water_temp
        elif limit_tbl_item == "salinity":  # 塩分濃度
            current_value = salinity
        elif limit_tbl_item == "do":  # 溶存酸素
            current_value = do
        else:
            pass

        # しきい値チェック（３回連続で範囲内のときフラグを"OK"に戻す）
        if (current_value >= limit_tbl_min) and (current_value <= limit_tbl_max):  # 正常の範囲内
            if limit_tbl_flg == "OK":  # フラグの値が"OK"なら何もしない
                pass
            elif limit_tbl_flg == "NG":  # フラグの値が"NG"なら"1"を立てる
                limit_tbl_flg = "1"
            elif limit_tbl_flg == "1":  # フラグの値が"1"なら"2"を立てる
                limit_tbl_flg = "2"
            elif limit_tbl_flg == "2":  # フラグの値が"2"なら"OK"を立て、復旧のLINEメッセージを設定
                alert_flg = "ON"  # アラート通知を"ON"にする（復旧のLINE通知）
                limit_tbl_flg = "OK"
                line_message = MESSAGE_SET(line_message) + "が範囲内になりました。"
            else:
                pass
        elif current_value < limit_tbl_min:  # 最低値を下回った場合
            if limit_tbl_flg == "OK":  # フラグの値が"OK"ならLINEアラート通知（低下）
                alert_flg = "ON"  # アラート通知を"ON"にする（発生のLINE通知）
                line_message = MESSAGE_SET(line_message) + "が設定値より低下しました。"
            else:
                pass
            limit_tbl_flg = "NG"  # リミットテーブルのフラグに"NG"を立てる

        elif current_value > limit_tbl_max:  # 最大値を上回った場合
            if limit_tbl_flg == "OK":  # フラグの値が"OK"ならLINEアラート通知（超過）
                alert_flg = "ON"  # アラート通知を"ON"にする（発生のLINE通知）
                line_message = MESSAGE_SET(line_message) + "が設定値を超過しました。"
            else:
                pass
            limit_tbl_flg = "NG"  # リミットテーブルのフラグに"NG"を立てる

        # リミットテーブルの更新
        sql = "UPDATE m_limit SET flg_sts = %s WHERE item = %s"
        cur2.execute(sql, (limit_tbl_flg, limit_tbl_item))

    # リミットテーブルの更新（測定値、取得再開の判断）
    cur2.execute("SELECT * FROM m_limit WHERE item = 'SYSTEM';")
    for row in cur2.fetchall():
        limit_tbl_flg = row[4]
    if limit_tbl_flg == "NG":
        alert_flg = "ON"  # アラート通知を"ON"にする（発生のLINE通知）
        line_message = line_message + "\n計測が再開されました。"
        # リミットテーブルの更新
        cur2.execute(
            "UPDATE m_limit SET flg_sts = 'OK' WHERE item = 'SYSTEM';")

    cur2.close()
    cur.close()
else:  # 古い測定値なので測定停止のアラート通知を行う
    cur = conn.cursor()
    cur.execute("SELECT * FROM m_limit WHERE item = 'SYSTEM';")
    for row in cur.fetchall():
        limit_tbl_flg = row[4]
    if limit_tbl_flg == "OK":
        alert_flg = "ON"  # アラート通知を"ON"にする（発生のLINE通知）
        line_message = line_message + "\n計測が停止しています。"
        # リミットテーブルの更新
        cur.execute(
            "UPDATE m_limit SET flg_sts = 'NG' WHERE item = 'SYSTEM';")
    cur.close()

conn.close()


# 新たにアラートが発生、又は復旧した場合はLINE通知する
if alert_flg == "ON":
    LINE_notify(line_message)  # LINEへ通知　<--- この行をコメントアウトすればLINE通知が止まる
    print(line_message)  # LINE通知の代わりにテストでメッセージを確認する為の画面表示
