#!/usr/bin/ python
# -*- coding: utf-8 -*-
"""
取得したデータを登録する処理
"""

from sshtunnel import SSHTunnelForwarder
import MySQLdb
import datetime
import getdata
import set_info
import sys
import err
import sshtunnel

data = None
ssh_info = None
mysql_info = None
mysql_columns = None
mysql_value = None
def get_infomation():
    """
    必要なデータを読み込み準備する関数
    """
    global data
    global ssh_info
    global mysql_info
    global mysql_columns
    global mysql_value
    data = getdata.get_sensor_data()             # 0 施設ID 1 水槽数 2 日付 3 時刻 4 水温 5 塩分濃度 6 溶存酸素濃度
    ssh_info = set_info.get_ssh_connect()        # 0 サーバアドレス 1 SSHポート 2 ユーザ名 3 パスワード 4 認証鍵 5 アクセスポート
    mysql_info = set_info.get_mysql_connect()    # 0 ホスト名 1 ユーザ名 2 パスワード 3 データベース 4 テーブル 5 エンコード 6 接続ポート
    mysql_columns_set = set_info.get_columns_info().split(',')

    # カラム名の整理と格納
    columns_count = len(mysql_columns_set)
    #print(columns_count)
    for i in range(int(columns_count)):
        if i == 0:
            mysql_columns = mysql_columns_set[i]
            mysql_value = '%s'
            i = i + 1
        else:
            mysql_columns = mysql_columns + ', ' + mysql_columns_set[i]
            mysql_value = mysql_value + ', %s'
            i = i + 1
    #print(mysql_columns)
    #print(mysql_value)
    print(ssh_info)
    #print(data)
    print(mysql_info)


def sql_sshtunnel_connect():
    """
    接続とデータベース登録を行う関数
    """
    get_infomation()
    global data
    global ssh_info
    global mysql_info
    global mysql_columns
    global mysql_value
    # サーバの仕様で直にMySQL接続できないので、一度サーバへSSH接続
    server = SSHTunnelForwarder(
        (str(ssh_info[0]), int(ssh_info[1])),
        ssh_password = str(ssh_info[3]),
        ssh_pkey = str(ssh_info[4]),
        ssh_username = str(ssh_info[2]),
        remote_bind_address = (str(ssh_info[0]), int(ssh_info[1])),
        local_bind_address = (str(mysql_info[0]), int(mysql_info[6])))
    # insert用処理準備
    sshtunnel.SSH_TIMEOUT = 5.0
    sshtunnel.TUNNEL_TIMEOUT = 5.0
    server.start()
    mysql_columns = '(' + mysql_columns + ') '
    insert_data = "values ( "+ mysql_value + ')'
    sql = 'insert into '+ mysql_info[3] + '.' + mysql_info[4] + mysql_columns + insert_data
    print(sql)
    conn = MySQLdb.connect(
        host=str(mysql_info[0]), user=mysql_info[1], password=mysql_info[2], db=mysql_info[3], charset=mysql_info[5], port=int(mysql_info[6]))
    try:
        cursor = conn.cursor()
        cursor.execute(sql, (data[0], data[1], data[2], data[3], data[4], data[5], data[6]))
        conn.commit()
    except:
        err.main()
        conn.close()
        server.close()
        sys.exit()
    finally:
        conn.close()
    server.close()



def debug_print():
  print(day)
  print(times)
  print(soil[0])
  print(soil[1])
  print(soil[2])
  print(temp1[0])
  print(temp1[1])

# メイン処理の呼び出し
#sql_sshtunnel_connect()
#debug_print()

def main():
    sql_sshtunnel_connect()


if __name__ == '__main__':
    main()
