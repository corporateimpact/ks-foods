#!/usr/bin/env python abort_measure LINE alert
# coding=utf-8
"""
----- ケーエスフーズIoT測定値異常時のLINE・メール通知 -----
"""
import time
import datetime
import smtplib
import requests
import mysql.connector
from common_module import common

# 上限値項目のカラムと物理名を紐付けるdictonayを作成
item_dict = {
    "water_temp": "水温",
    "salinity": "塩分濃度",
    "do": "溶存酸素"
}

# -----データベースの情報を格納する定数-----
COMMON_DB_USER = "root"  # 共通DBのユーザ名
COMMON_DB_PASS = "pm#corporate1"  # 共通DBのパスワード
COMMON_DB_HOST = "localhost"  # 共通DBのホスト名
COMMON_DB_NAME = "common_db"  # 共通DBのDB名

PJ_DB_USER = "root"  # プロジェクトで使用するDBのユーザ名
PJ_DB_PASS = "pm#corporate1"  # プロジェクトで使用するDBのパスワード
PJ_DB_HOST = "localhost"  # プロジェクトで使用するDBのホスト名
PJ_DB_NAME = "ksfoods"  # プロジェクトで使用するDB名
# ---------------------------------------

# -----グローバル変数群-----
limit_tbl_item = None  # 規定値テーブルの項目名
current_value = None  # 既定値テーブルの現在値
smtp_addr = None  # 送信元アドレス
smtp = None  # 送信元接続情報
line_message = "<< ケーエスフーズ：アラート通知 >>"  # LINE通知のメッセージタイトル
subject_head = line_message
alert_flg = "OFF"  # LINEアラートが発生したら"ON"
# --------------------------

# 10分前の時刻を取得
before_10min = time.time() - 600
before_10min = datetime.datetime.fromtimestamp(before_10min)

# 30分前の時刻を取得
before_30min = time.time() - 1800
before_30min = datetime.datetime.fromtimestamp(before_30min)


def main():
    """
    メイン関数
    """
    # メールサーバ接続処理
    init_smtp()

    # データ取得処理を呼び出し
    get_data()

    # 新たにアラートが発生、又は復旧した場合はLINE通知する
    if alert_flg == "ON":
        # LINEへ通知
        send_line_message(line_message)  # <--- この行をコメントアウトすればLINE通知が止まる
        print(line_message)  # LINE通知の代わりにテストでメッセージを確認する為の画面表示

        # メール設定処理を呼び出し
        set_mail_message()


def send_line_message(str_message):
    """
    LINE Notifyに接続し、LINE送信を行う処理
    """

    url = "https://notify-api.line.me/api/notify"

    # 共通モジュールのLINEトークン取得処理を呼び出し
    common.get_line_token()

    # get_line_tokenで取得したトークンをもとにヘッダーを作成する
    headers = {"Authorization": "Bearer " + common.line_token}
    payload = {"message":  str_message}

    # *********************************************************
    # 動作確認のためコメントアウト
    r = requests.post(url, headers=headers, params=payload)
    # *********************************************************


def set_line_message(str_message):
    """
    LINEへ通知するメッセージを設定
    """
    global limit_tbl_item

    if limit_tbl_item == "water_temp":
        str_message = str_message + "\n" + \
            item_dict["water_temp"] + "(" + format(current_value) + "℃)"
    elif limit_tbl_item == "salinity":
        str_message = str_message + "\n" + \
            item_dict["salinity"] + "(" + format(current_value) + "%)"
    elif limit_tbl_item == "do":
        str_message = str_message + \
            "\n" + item_dict["do"] + "(" + format(current_value) + "mg/L)"
    else:
        pass
    return str_message


def init_smtp():
    """
    smtpの設定処理
    """
    global smtp
    global smtp_addr

    # データベースに格納してあるSMTPの情報を取得する
    smtp_cur = common.connect_database_project()
    # 送信元のアドレスはプロジェクトごとに一意であること。
    smtp_cur.execute("SELECT * FROM m_smtp_mail;")

    for smtp_row in smtp_cur.fetchall():
        # SQLで取得したSMTPの情報を各変数に分割して格納
        smtp_host = smtp_row[1]
        smtp_port = smtp_row[2]
        smtp_name = smtp_row[3]
        smtp_pass = smtp_row[4]
        smtp_addr = smtp_row[5]

    # 取得したSMTPの情報をもとにSMTP接続する
    smtp = smtplib.SMTP(smtp_host, smtp_port)
    smtp.login(smtp_name, smtp_pass)

    # 後処理としてクローズ処理を実行する
    common.close_con_connect(common.pj_con, smtp_cur)


def set_mail_message():
    """
    送信するメールメッセージの設定を行う処理
    アラートごとの件名とメッセージを設定する
    """

    mail_cur = common.connect_database_project()

    # メールの件名を作成する
    mail_subject = subject_head

    # メールの本文を作成する
    mail_body = line_message

    # メール配信フラグがONになっているデータを取得するSQL
    sel_mail_sql = "SELECT * FROM m_mail WHERE SEND_FLAG = 'ON';"
    mail_cur.execute(sel_mail_sql)

    # メールマスタから取得した、対象のメールアドレス分ループさせる。
    for mail_row in mail_cur.fetchall():
        # SQLで取得したデータを変数へ格納。IDとフラグは保持しない
        # mail_id = mail_row[0]
        # mail_name = mail_row[1]
        mail_address = mail_row[3]

        print(mail_address)

        # 送信対象のメールアドレスにメールを送信する
        send_mail(mail_address, mail_subject, mail_body)


def send_mail(to_address, str_subject, str_body):
    """
    メール送信を行う処理
    """

    print("address:" + to_address)
    print("subject:" + str_subject)
    print("body:" + str_body)

    # 送信メールの本文を作成する
    mail_message = ("From: %s\r\nTo: %s\r\nSubject: %s\r\n\r\n%s" %
                    (to_address, to_address, str_subject, str_body))

    # メール送信処理
    smtp.sendmail(smtp_addr, to_address, mail_message.encode('utf-8'))

    print(mail_message)


def get_data():
    """
    測定値テーブルに接続して、測定値を取得する処理
    """

    data_cur = common.connect_database_project()

    # 直近のデータを取得するSQL句の発行
    sel_sql = "SELECT * FROM data ORDER BY day DESC, time DESC LIMIT 1;"

    # SQLを実行する
    data_cur.execute(sel_sql)

    for row in data_cur.fetchall():
        fact_id = row[0]  # 工場ID
        tank_no = row[1]  # 水槽ID
        day_tbl = row[2]  # 日付
        time_tbl = row[3]  # 時刻
        water_temp = row[4]  # 水温
        salinity = row[5]  # 塩分濃度
        do = row[6]  # 溶存酸素

        # デバッグ用。取得した値を出力する
        print("工場ID：" + str(fact_id))
        print("水槽ID：" + str(tank_no))
        print("日付：" + str(day_tbl))
        print("時間：" + str(time_tbl))
        print("水温" + str(water_temp))
        print("塩分濃度" + str(salinity))
        print("溶存酸素" + str(do))

    # 後処理
    common.close_con_connect(common.pj_con, data_cur)

    # 測定値チェック処理の呼び出し（引数には取得した測定値を渡す）
    check_data(day_tbl, time_tbl, water_temp, salinity, do)


def check_data(data_day, data_time, data_w_temp, data_salinity, data_do):
    """
    取得した測定値のチェック処理
    """
    global alert_flg
    global line_message
    global limit_tbl_item
    global current_value

    # 測定値が直近のものか(10分前と比較)判断、測定が止まっていればアラート通知
    day_time = format(data_day) + " " + format(data_time) + ".999999"

    # SYSTEMの値を取得するSQL
    sel_sys_sql = "SELECT * FROM m_limit WHERE item = 'SYSTEM';"

    print("現在：" + str(day_time))
    print("10分前：" + str(before_10min))
    print("30分前：" + str(before_30min))

    # 測定値の時間と、30分前の時間を比較する
    if format(day_time) <= format(before_30min):
        # 測定値が最新でない場合、測定停止のアラート通知を行う
        alert_cur = common.connect_database_project()

        # SYSTEMの値を更新するSQL
        upd_sys_sql = "UPDATE m_limit SET flg_sts = 'NG' WHERE item = 'SYSTEM';"

        alert_cur.execute(sel_sys_sql)

        for row in alert_cur.fetchall():
            limit_tbl_flg = row[4]
        if limit_tbl_flg == "OK":
            alert_flg = "ON"  # アラート通知を"ON"にする（発生のLINE通知）
            line_message = line_message + "\n測定が停止しています。"
            # リミットテーブルの更新
            alert_cur.execute(upd_sys_sql)

            # commitしてDBに反映
            common.pj_con.commit()
        alert_cur.close()

    else:
        # 測定値が最新の場合、しきい値チェック処理
        check_cur = common.connect_database_project()
        update_cur = common.pj_con.cursor()

        # しきい値を取得するSQL
        sel_check_sql = "SELECT * FROM m_limit WHERE item IN ('water_temp', 'salinity', 'do');"

        # しきい値を取得するSQLの実行
        check_cur.execute(sel_check_sql)

        # しきい値テーブルから取得した値を変数に格納
        for row in check_cur.fetchall():
            limit_tbl_item = row[1]
            limit_tbl_max = row[2]
            limit_tbl_min = row[3]
            limit_tbl_flg = row[4]

            # 各項目の測定値をチェック
            if limit_tbl_item == "water_temp":  # 水温
                current_value = data_w_temp
            elif limit_tbl_item == "salinity":  # 塩分濃度
                current_value = data_salinity
            elif limit_tbl_item == "do":  # 溶存酸素
                current_value = data_do
            else:
                pass

            # しきい値のチェックを行う（３回連続で範囲内のときフラグを"OK"に戻す）
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
                    line_message = set_line_message(
                        line_message) + "が範囲内になりました。"
                    print("範囲内")
                else:
                    pass
            elif current_value < limit_tbl_min:  # 最低値を下回った場合
                if limit_tbl_flg == "OK":  # フラグの値が"OK"ならLINEアラート通知（低下）
                    alert_flg = "ON"  # アラート通知を"ON"にする（発生のLINE通知）
                    line_message = set_line_message(
                        line_message) + "が設定値より低下しました。"
                    print(str(line_message))
                else:
                    pass
                limit_tbl_flg = "NG"  # リミットテーブルのフラグに"NG"を立てる

            elif current_value > limit_tbl_max:  # 最大値を上回った場合
                if limit_tbl_flg == "OK":  # フラグの値が"OK"ならLINEアラート通知（超過）
                    alert_flg = "ON"  # アラート通知を"ON"にする（発生のLINE通知）
                    line_message = set_line_message(
                        line_message) + "が設定値を超過しました。"
                    print(str(line_message))
                else:
                    pass
                limit_tbl_flg = "NG"  # リミットテーブルのフラグに"NG"を立てる

            # リミットテーブルの更新
            upd_limit_sql = "UPDATE m_limit SET flg_sts = '%s' WHERE item = '%s';" % (
                limit_tbl_flg, limit_tbl_item)
            update_cur.execute(upd_limit_sql)

            # commitしてDBに反映する
            common.pj_con.commit()

            print("変数展開したSQL" + upd_limit_sql)

        # リミットテーブルの更新（測定値、取得再開の判断）
        check_cur.execute("select * from m_limit where item = 'SYSTEM';")

        for row in check_cur.fetchall():
            limit_tbl_flg = row[4]
        if limit_tbl_flg == "NG":
            alert_flg = "ON"  # アラート通知を"ON"にする（発生のLINE通知）
            line_message = line_message + "\n計測が再開されました。"
            print("計測再開")
            # リミットテーブルの更新
            update_sql = "UPDATE m_limit SET flg_sts = 'OK' WHERE item = 'SYSTEM';"
            update_cur.execute(update_sql)
            # commitしてDBに反映
            common.pj_con.commit()

        check_cur.close()
        update_cur.close()


# メイン関数を実行
if __name__ == "__main__":
    main()
