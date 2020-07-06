#!/usr/bin/env python
#coding: utf-8
"""
ACTW-CAD ワイパー式有線水温塩分計から値を取得するスクリプト
RS-485接続
"""
import serial
import time
import binascii

data = None
data_access = None
data_dummy = None
ser = None

def main():
    global data
    global data_access
    global ser
    global data_dummy
    """
    メイン処理
    """
    # 接続設定
    dev = "/dev/ttyUSB_RS485"                                             # 接続先指定
    rate = 38400
    ser = serial.Serial(dev, rate, timeout=1)
    # data_access = ['?', '0', '0', ',', 'P', 'V', 'A', 'L', 0x0d]
    data_access = [0x3f, 0x30, 0x30, 0x2c, 0x50, 0x56, 0x41, 0x4c, 0x0d]  # 観測したデータ出力
    data_dummy = [0x3f, 0x30, 0x30, 0x2c, 0x56, 0x45, 0x41, 0x52, 0x2c, 0x0d]    # リセット用のダミー処理
    data_read()                                                           # データ要求・受け取り処理
    ser.close()                                                           # 接続終了
    print(data)

    return data


def data_read():
    """
    データ読み出し処理
    """
    global data
    # serial通信記述 ASCII文字
    #data_access = ['?', '0', '0', ',', 'D', 'A','T', 'E', ',', 0x0d]   # 機器に設定されている日付の取得
    ser.write(data_access)                                              # 命令書き込み
    time.sleep(0.2)
    ser_response = ser.readline()                                       # 読み出し
    #ser_response = (binascii.hexlify(ser_response),'utf-8')                                # エンコード
    data = []
    # レスポンス内容 [ID, 命令内容, 電気伝導度(ms/cm), 水温, 塩分, 電源電圧]
    data = str(ser_response).split(',')                                      # 配列に格納

    # データ確認
    data_count = len(data)
    for i in range(5):                            # 正常に読み込めないと帰ってくる要素数が減る
        if data_count < 5:                        # 読み込めなかったときはリセット→リトライ(最大5回)
            # reset
            ser.write(data_dummy)
            ser_response = ser.readline()
            time.sleep(0.2)
            # リトライ
            ser.write(data_access)
            ser_response = ser.readline()         # 読み出し
            #ser_response = ser_response.encode()  # エンコード
            data = []
            data = str(ser_response).split(',')        # 配列に格納
            data_count = len(data)
        if data_count > 5:                        # 正常数出力後はループ脱出
            break

    # 以降closeまでダミー処理 同じ内容で通信を2回以上行うと以降のデータが読み取れない？のでリセット用(ver確認構文)
    ser.write(data_dummy)
    ser_response = ser.readline()


if __name__ == '__main__':
    main()
