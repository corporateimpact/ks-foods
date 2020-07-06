#!/usr/bin/env python
#coding: utf-8
"""
ACTW-CAD ワイパー式有線水温塩分計のワイパー作動用スクリプト
RS-485接続
ワイパー動作中は他の命令を送らないように注意(正常動作しません)
"""
import serial
import time


def main():
    """
    メイン処理　ワイパー作動
    """
    # 接続設定
    dev = "/dev/ttyUSB_RS485"                                                          # 接続先指定
    rate = 38400
    ser = serial.Serial(dev, rate, timeout=1)

    # serial通信記述 python3で呼び出すので
    data_access = [0x3f, 0x30, 0x30, 0x2c, 0x57, 0x49, 0x50, 0x45, 0x2c, 0x30, 0x2c, 0x0d]        # ワイパー作動命令
    ser.write(data_access)                                                             # 命令書き込み
    ser_response = ser.readline()                                                      # 読み出し
    time.sleep(10)                                                                     # 動作中10秒間停止
    # 以降closeまでダミー処理　リセット用(ver確認構文)
    data_access = [0x3f, 0x30, 0x30, 0x2c, 0x56, 0x45, 0x41, 0x52, 0x2c, 0x0d]         # リセット用のダミー処理
    ser.write(data_access)
    ser_response = ser.readline()
    ser_response = str(ser_response).split(',')
    ser.close()


if __name__ == '__main__':
    main()
