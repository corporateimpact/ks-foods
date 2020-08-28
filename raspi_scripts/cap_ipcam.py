"""
IPカメラから静止画を撮影してアップロードする処理
"""

#!/usr/bin/ python
# -*- coding: utf-8 -*-

import sys
import os
import subprocess
import cv2
import cv2 as cv
import configparser
import err  # 共通エラー処理モジュール
import set_info  # 共通情報取得モジュール
import datetime

# 変数宣言

camera_list = None
ipcamera_list = None
dir_info = None
daytime = None
image_info = None
image_upload_files = None
image_mini_files = None
ssh_connect = None
e_check = None
logtime = datetime.datetime.now().strftime('[%Y-%m-%d %H:%M:00]')

def upload_files():
    """
    サーバにSSH接続して、画像を保存する
    """

    global dir_info
    global ssh_connect
    global camera_list
    global daytime

    i = 0  # 念のためリセット
    for i in range(int(camera_list)):
        dt_now = datetime.datetime.now()
        i += 1
        make_dir_call = 'sudo ssh ' + \
            ssh_connect[2] + '@' + ssh_connect[0] + ' mkdir -p ' + \
            dir_info[2] + str(i) + '/' + daytime[0] + '/'
        subprocess.call(make_dir_call.split())
        # print(make_dir_call)
        upload_call = 'sudo scp -C ' + dir_info[1] + 'images/' + str(i) + '/' + daytime[0] + '/' + daytime[0] + '_' + \
                      daytime[1] + '00.jpg ' + ssh_connect[2] + '@' + ssh_connect[0] + \
                      ':' + dir_info[2] + str(i) + '/' + daytime[0] + '/'
        try:
            subprocess.check_call(upload_call.split())
        except subprocess.CalledProcessError as e:
            print("[" + dt_now.strftime('%Y-%m-%d %H:%M:%S') + "]" + str(e))
        else:
            pass
        # print(upload_call)
        upload_call = 'sudo scp -C ' + dir_info[1] + 'images/' + str(i) + '/' + daytime[0] + '/' + daytime[0] + '_' + daytime[1] + \
            '00_mini.jpg ' + ssh_connect[2] + '@' + ssh_connect[0] + \
            ':' + dir_info[2] + str(i) + '/' + daytime[0] + '/'
        try:
            subprocess.check_call(upload_call.split())
        except subprocess.CalledProcessError as e:
            print("[" + dt_now.strftime('%Y-%m-%d %H:%M:%S') + "]" + str(e))
        else:
            pass


        # print(upload_call)


def set_infomation():
    """
    各種情報の取得・設定処理
    """

    global camera_list
    global ipcamera_list
    global dir_info
    global daytime
    global image_info
    global ssh_connect

    camera_list = set_info.get_camera_list()
    ipcamera_list = []
    ipcamera_list = set_info.get_camera_rstp()
    dir_info = []
    # 0 メイン 1 データ 2 cloud
    dir_info = set_info.get_dir_info()
    daytime = []
    daytime = set_info.get_daytime()                                 # 0 日付 1 時間HHMM
    image_info = []
    # 0 org 1 upload 2 mini
    image_info = set_info.get_image_info()
    ssh_connect = []
    # 0 cloud_address 1 ssh_port 2 user 3 pass 4 key 5 port
    ssh_connect = set_info.get_ssh_connect()


def image_cap():
    """
    静止画の撮影・保存を行う処理
    """

    global camera_list
    global ipcamera_list
    global dir_info
    global daytime
    global image_info
    global image_upload_files
    global image_mini_files
    global e_check
    global logtime

    # 静止画の撮影用
    i = 0
    camera_no = 1  # 周回確認用

    # 0_height 1_width 2_quality
    org_imageinfo = image_info[0].split(', ')
    upload_imageinfo = image_info[1].split(', ')
    mini_imageinfo = image_info[2].split(', ')
    original_w_h = (int(org_imageinfo[0]), int(org_imageinfo[1]))
    #upload_w_h = (upload_imageinfo[0] + ',' + upload_imageinfo[1])
    mini_w_h = (int(mini_imageinfo[0]), int(mini_imageinfo[1]))
    # print(original_w_h)
    # print(mini_w_h)
    # print(org_imageinfo[2])
    for i in range(int(camera_list)):
        image_dir = str(dir_info[1]) + 'images/' + \
            str(camera_no) + '/' + daytime[0]
        # print(image_dir)
        if not os.path.exists(image_dir):
            os.mkdir(image_dir)
        # 書込設定
        image_org_files = str(
            image_dir) + '/' + daytime[0] + '_' + daytime[1] + '00_org.jpg'  # ローカル保存用
        image_timestamp_files = image_org_files
        image_upload_files = str(
            image_dir) + '/' + daytime[0] + '_' + daytime[1] + '00.jpg'  # アップロード用
        image_mini_files = str(
            image_dir) + '/' + daytime[0] + '_' + daytime[1] + '00_mini.jpg'  # サムネイル用
        # print(image_org_files)
        # print(image_upload_files)
        # print(image_mini_files)
        dt_now = datetime.datetime.now()
        # 撮影開始
        cap = cv2.VideoCapture(ipcamera_list[i])
        # print(ipcamera_list[i])
        cap.set(cv2.CAP_PROP_POS_FRAMES, 3)
        c = 1
        for c in range(3):
            ret, frame = cap.read()
            if ret:
                org_image = cv2.resize(frame, original_w_h)
                cv2.imwrite(image_org_files, org_image, [
                            int(cv2.IMWRITE_JPEG_QUALITY), int(org_imageinfo[2])])  # オリジナル画像の保存
                c = 0
                # 成功したら抜ける
                break
            else:
                c += 1
                print("[" + dt_now.strftime('%Y-%m-%d %H:%M:%S') + "] Capture NG.")
        # 終了
        cap.release()
        # 4回以上の場合はエラー判定
        if c >= 4:
            print("[" + dt_now.strftime('%Y-%m-%d %H:%M:%S') + "] Camera " + str(camera_no) + " Connection Error.")

        # 20200825処理追加　画像にタイムスタンプを付与する
        timestamp_img = cv.imread(image_org_files)
        timetext = dt_now.strftime('%Y-%m-%d %a %H:%M:00')
        cv.putText(timestamp_img, timetext, (50, 100), cv.FONT_HERSHEY_PLAIN, 4, (255, 255, 255), 10, cv.LINE_AA)  # 白文字太め
        cv.putText(timestamp_img, timetext, (50, 100), cv.FONT_HERSHEY_PLAIN, 4, (0, 0, 0), 3, cv.LINE_AA)  # 黒文字細め
        cv.imwrite(image_timestamp_files, timestamp_img)
        # 20200825追加ここまで
        # アップロード用の画像作成　サイズはそれぞれ固定なので値そのまま書き込み
        # オリジナルファイル読み込み
        load_image = cv2.imread(image_org_files)
        cv2.imwrite(image_upload_files, load_image, [
                    int(cv2.IMWRITE_JPEG_QUALITY), int(upload_imageinfo[2])])  # アップ用
        try:
            up_mini = cv2.resize(load_image, mini_w_h)
            cv2.imwrite(image_mini_files, up_mini, [
                int(cv2.IMWRITE_JPEG_QUALITY), int(mini_imageinfo[2])])  # サムネイル
        except:
            # 読み込み失敗の場合はエラーカウント増加
            print("[" + dt_now.strftime('%Y-%m-%d %H:%M:%S') + "] Image " + str(camera_no) + " Import Error.")
            e_check =+ 1
        finally:
            camera_no = int(camera_no) + 1


def error_check():
    """
    エラー判定する関数
    （カメラへのアクセスが全NGだった場合はエラーカウントを追加する
    """
    global e_check
    global camera_list
    if e_check == int(camera_list):
        err.main()


def main():
    """
    メイン関数
    """
    set_infomation()
    image_cap()
    upload_files()
    error_check()


# main関数を呼び出す
if __name__ == '__main__':
    main()
