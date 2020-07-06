#!/usr/bin/env python
#coding: utf-8
"""
ACTW-CAD ワイパー式有線水温塩分計から値を取得するスクリプト
RS-485接続
"""
import serial
import time


def main():
    """
    メイン処理　データの読み出し
    """
    # 接続設定
    dev = "/dev/ttyUSB_RS485"                                           # 接続先指定
    rate = 38400
    ser = serial.Serial(dev, rate, timeout=1)

    # serial通信記述 ASCII文字
    data_access = ['?', '0', '0', ',', 'P', 'V', 'A', 'L', 0x0d]        # 観測したデータ出力
    #data_access = ['?', '0', '0', ',', 'D', 'A','T', 'E', ',', 0x0d]   # 機器に設定されている日付の取得
    ser.write(data_access)                                              # 命令書き込み
    time.sleep(0.2)
    ser_response = ser.readline()                                       # 読み出し
    ser_response = ser_response.encode()                                # エンコード
    data = []
    # レスポンス内容 [ID, 命令内容, 電気伝導度(ms/cm), 水温, 塩分, 電源電圧]
    data = ser_response.split(',')                                      # 配列に格納
    print(data)
    data_count = len(data)
    if data_count < 5:
        ser_response = ser.readline()  # 読み出し
        ser_response = ser_response.encode()  # エンコード
        data = []
        data = ser_response.split(',')  # 配列に格納
        print(data)

    # 以降closeまでダミー処理 同じ内容で通信を2回以上行うと以降のデータが読み取れない？のでリセット用(ver確認構文)
    data_access = ['?', '0', '1', ',', 'V', 'E', 'A', 'R', ',', 0x0d]
    ser.write(data_access)
    ser_response = ser.readline()
    ser_response = ser_response.encode()
    ser.close()
    return data


if __name__ == '__main__':
    main()
