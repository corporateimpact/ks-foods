#!/usr/bin/ python
# -*- coding: utf-8 -*-
"""
各センサーデータを集積する処理
"""
import t3_do_logger
import actw_wtemp_logger
import set_info
import sys
import err
import subprocess

do_average = None
water_salt_data = None
salinity_data = None
daytime = None
factory_info = None

def get_sensor_data():
    """
    各データを格納する関数
    """
    global do_average
    global water_salt_data
    global salinity_data
    global daytime
    global factory_info
    # DO値
    do_data = t3_do_logger.main()
    do_1 = do_data[0]
    do_2 = do_data[1]
    do_3 = do_data[2]
    do_denominator = len(do_data) - do_data.count(None)    # 未取得(None)がある場合は分母数から引く
    if do_denominator == 0:    # 取得値がすべて0の場合はエラー処理
        err.main()
        sys.exit()
    do_average = round(((float(do_1) + float(do_2) + float(do_3)) / do_denominator), 2) # n回計測からの平均値
    # 水温と塩分濃度
    water_salt_data = actw_wtemp_logger.main()
    water_temp_data = water_salt_data[3]
    salinity_data = float(water_salt_data[4]) / 10
    #water_temp_data = 15.0
    #salinity_data = 2.9
    # その他情報
    daytime = set_info.get_daytime2()             # 0 日付 1 時刻 (セパレート記号付き)
    factory_info = set_info.get_factory_info()    # 0 施設ID 2 水槽数
    # 0 施設ID 1 水槽数 2 日付 3 時刻 4 水温 5 塩分濃度 6 溶存酸素濃度
    return factory_info[0], factory_info[1], daytime[0], daytime[1], water_temp_data, salinity_data, do_average


def main():
    get_sensor_data()


if __name__ == '__main__':
    main()



