package com.example.user.fich;

import android.app.Notification;
import android.app.NotificationManager;
import android.app.Service;
import android.content.Intent;
import android.location.Criteria;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.os.Binder;
import android.os.Bundle;
import android.os.Handler;
import android.os.IBinder;
import android.os.Message;
import android.util.Log;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Timer;
import java.util.TimerTask;

public class LocService extends Service {
    private final IBinder binder = new ServiceBinder();
    private static final String BROADCAST_LocData = "GAWorkShop.Fich.SendLocData";
    private Timer timer; // 用來定時定位的Timer
    static final long taskDelay = 0; // 啟動Timer的延遲時間 : 立即
    private long taskPeriod = 300000; // Timer每次任務的間隔時間 : 300秒 **每決定一次位置用3個定位點平均的話間隔至少是90秒,平均數越多間隔要越長**
    static final int MIN_TIME = 1000; // 位置偵測更新條件：1000 毫秒
    static final float MIN_DIST = 0; // 位置偵測更新條件：0 公尺
    private LocListener locListener; // 監聽位置變動的監聽器
    LocationManager lm; // 定位總管
    private int locateCount = 0; // 暫存定了幾次的計數器
    static final int locateTimes = 3; // 決定一次位置要用 3 個定位點來平均

    public class ServiceBinder extends Binder {
        LocService getService() {
            return LocService.this;
        }
    }

    public void onCreate() {
        super.onCreate();
        timer = new Timer();
        locListener = new LocListener();
    }

    public int onStartCommand(Intent intent, int flags, int startId){
        // 服務啟動時就開啟定時定位的Timer
        timer.schedule(new TimerTask() {
            @Override
            public void run() {
                myHandler.sendEmptyMessage(0); // 定位的方法,詳見下
            }
        }, taskDelay, taskPeriod);
        return START_STICKY;
    }

    @Override
    public IBinder onBind(Intent intent) {
        // TODO: Return the communication channel to the service.
        System.out.println("Bind LocService success");
        return binder;
    }

    public boolean onUnbind(Intent intent){
        System.out.println("Unbind LocService");
        return false;
    }

    public void onDestroy() {
        super.onDestroy();
        System.out.println("destroy the LocService");
        timer.cancel();
        timer = null;
        try {
            lm.removeUpdates(locListener);
        }catch (SecurityException e){

        }
    }

    private final Handler myHandler = new Handler(){
        //定位
        @Override
        public void handleMessage(Message msg)
        {
            lm = (LocationManager) getSystemService(LOCATION_SERVICE);
            Log.e("~~DEBUG~~", "lm : "+ lm.toString());
            String bestProvider = lm.getBestProvider(new Criteria(), true); // 取得最佳的定位提供者
            if (bestProvider.equals("gps") || bestProvider.equals("network")) { // 有開定位
                System.out.println("Location : On , the locating provider is " + bestProvider);
                try {
                    lm.requestLocationUpdates(bestProvider, MIN_TIME, MIN_DIST, locListener);
                }catch (SecurityException e){

                }
            } else { // 沒開定位,傳送通知提醒
                System.out.println("Location : Off , please check the location setting");
                //用通知發送訊息 : 沒開定位
                NotificationManager notificationManager =
                        (NotificationManager) getSystemService(NOTIFICATION_SERVICE);
                notificationManager.cancelAll();
                Notification notification = new Notification.Builder(LocService.this)
                        .setTicker("Fich定位服務提醒")
                        .setContentTitle("Fich無法紀錄位置資訊")
                        .setContentText("需開啟裝置上的定位服務")
                        .setSmallIcon(android.R.drawable.ic_menu_info_details)
                        .setAutoCancel(true)
                        .setShowWhen(true)
                        .build();
                notificationManager.notify(0, notification);
            }
        }
    };

    double tempLongitude = 0;
    double tempLatitude = 0;
    public void locCollect(Location location){
        tempLongitude += location.getLongitude();
        tempLatitude += location.getLatitude();

        if(locateCount < locateTimes-1){
            locateCount++;
        }else if(locateCount == locateTimes-1){
            //統計並送出
            Intent intent = new Intent(BROADCAST_LocData);
            Bundle bundle = new Bundle();
            bundle.putDouble("longitude",tempLongitude/locateTimes);
            bundle.putDouble("latitude",tempLatitude/locateTimes);
            bundle.putLong("time",location.getTime());
            intent.putExtras(bundle);
            sendBroadcast(intent);
            locateCount = 0;
            tempLongitude = 0;
            tempLatitude = 0;
            try {
                lm.removeUpdates(locListener);
            }catch (SecurityException e){

            }
        }
    }

    //更改taskPeriod每次定位間隔時間的方法
    public void setTaskPeriod(int minute){
        timer.cancel();
        timer = new Timer();
        taskPeriod = minute * 60000; //分鐘轉毫秒
        tempLongitude = 0;
        tempLatitude = 0;
        locateCount = 0;
        try {
            lm.removeUpdates(locListener);
        }catch (SecurityException e){

        }
        timer.schedule(new TimerTask() {
            @Override
            public void run() {
                myHandler.sendEmptyMessage(0);
            }
        }, taskDelay, taskPeriod);
        System.out.println("已更改定時定位間隔時間為 " + minute + " 分鐘");
    }

    public class LocListener implements LocationListener {

        @Override
        public void onLocationChanged(Location location) {
            String str="平均定位中 : "+(locateCount+1)+" / "+locateTimes;
            str+= "\n定位提供者:"+location.getProvider();
            str+= String.format("\n經度:%.6f\n緯度:%.6f\n時間:%s\n",
                    location.getLongitude(),	// 經度
                    location.getLatitude(),		// 緯度
                    new SimpleDateFormat("yyyy年MM月dd日 HH:mm").format(new Date(location.getTime()))); // 時間
            str+= "--------------------";
            System.out.println(str);
            locCollect(location);
        }

        @Override
        public void onStatusChanged(String provider, int status, Bundle extras) {

        }

        @Override
        public void onProviderEnabled(String provider) {

        }

        @Override
        public void onProviderDisabled(String provider) {

        }
    }
}
