#!/usr/bin/ python
# -*- coding: utf-8 -*-

"""
取得したデータを登録する処理
"""
import sshtunnel
from sshtunnel import SSHTunnelForwarder    # 別々に読み込まないとエラー
import MySQLdb
import getdata
import set_info
import sys
import err

# global
data = None
ssh_info = None
mysql_info = None
mysql_columns = None
mysql_value = None
sql = None

def get_infomation():
    """
    必要なデータを読み込み準備する関数
    """
    global data
    global ssh_info
    global mysql_info
    global mysql_columns
    global mysql_value
    global sql
    data = getdata.get_sensor_data()             # 0 施設ID 1 水槽数 2 日付 3 時刻 4 水温 5 塩分濃度 6 溶存酸素濃度
    ssh_info = set_info.get_ssh_connect()        # 0 サーバアドレス 1 SSHポート 2 ユーザ名 3 パスワード 4 認証鍵 5 アクセスポート
    mysql_info = set_info.get_mysql_connect()    # 0 ホスト名 1 ユーザ名 2 パスワード 3 データベース 4 テーブル 5 エンコード 6 接続ポート
    mysql_columns_set = set_info.get_columns_info().split(',')

    # mysql構文の整理と格納
    columns_count = len(mysql_columns_set)
    for i in range(int(columns_count)):
        if i == 0:
            mysql_columns =  ' (' + mysql_columns_set[i]
            mysql_value = 'values (%s'
            i = i + 1
        else:
            mysql_columns = mysql_columns + ', ' + mysql_columns_set[i]
            mysql_value = mysql_value + ', %s'
            i = i + 1
    mysql_columns = mysql_columns + ') '
    insert_data = mysql_value + ')'
    sql = 'insert into ' + mysql_info[3] + '.' + mysql_info[4] + mysql_columns + insert_data


def sql_sshtunnel_connect():
    """
    接続とデータベース登録を行う関数
    """
    global data
    global ssh_info
    global mysql_info
    global mysql_columns
    global mysql_value
    # 構文準備
    #print(sql)
    # SSHポートフォワーディング
    with SSHTunnelForwarder(
            (str(ssh_info[0]), int(ssh_info[1])),
            ssh_password='pm#corporaet1',    # ここ変数にすると確実にエラーが起きる
            ssh_pkey=str(ssh_info[4]),
            ssh_username=str(ssh_info[2]),
            remote_bind_address=(str(ssh_info[0]), 3306), # ここ変数にすると確実にエラーが起きる
            local_bind_address=(str(mysql_info[0]), 3306), # ここ変数にすると確実にエラーが起きる
        ) as server:
        conn = MySQLdb.connect(
            host=str(mysql_info[0]),
            user=str(mysql_info[1]),
            password=str(mysql_info[2]),
            db=str(mysql_info[3]),
            charset=str(mysql_info[5]),
            port=int(mysql_info[6]),
        )
        try:
            cursor = conn.cursor()
            cursor.execute(sql, (data[0], data[1], data[2], data[3], float(data[4]), float(data[5]), float(data[6])))
            conn.commit()
        except:     # エラー時処理呼び出し
            conn.close()
            err.main()
            sys.exit()
        finally:    # 終了処理
            conn.close()



def debug_print():
    """
    確認用に情報出力する関数
    """
    print(data)
    print(ssh_info)
    print(mysql_info)
    print(mysql_columns)
    print(mysql_value)
    print(sql)


def main():
    get_infomation()
    #debug_print()
    sql_sshtunnel_connect()


if __name__ == '__main__':
    main()
