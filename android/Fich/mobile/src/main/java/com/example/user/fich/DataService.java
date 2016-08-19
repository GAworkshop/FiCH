package com.example.user.fich;

import android.app.Service;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.os.Binder;
import android.os.Bundle;
import android.os.IBinder;

import java.util.ArrayList;

public class DataService extends Service {
    private final IBinder binder = new ServiceBinder();
    private static final String BROADCAST_LocData = "GAWorkShop.Fich.SendLocData";
    private ArrayList<Location> locList = new ArrayList<Location>();
    private LocDataReceiver locDataReceiver;

    public class ServiceBinder extends Binder {
        DataService getService() {
            return DataService.this;
        }
    }

    public void onCreate(){
        super.onCreate();
        //註冊接收位置資訊的receiver
        IntentFilter filter = new IntentFilter(BROADCAST_LocData);
        locDataReceiver = new LocDataReceiver();
        registerReceiver(locDataReceiver,filter);
    }

    @Override
    public IBinder onBind(Intent intent) {
        // TODO: Return the communication channel to the service.
        System.out.println("Bind DataService success");
        return binder;
    }

    public boolean onUnbind(Intent intent){
        System.out.println("Unbind DataService");
        return false;
    }

    public void onRebind(Intent intent){
        System.out.println("Rebind DataService");
    }

    public void onDestroy(){
        super.onDestroy();
        unregisterReceiver(locDataReceiver);
    }

    private class LocDataReceiver extends BroadcastReceiver{

        @Override
        public void onReceive(Context context, Intent intent) {
            Bundle bundle = intent.getExtras();
            locList.add(new com.example.user.fich.Location(bundle.getDouble("longitude"),
                                     bundle.getDouble("latitude"),
                                     bundle.getLong("time")));
            System.out.println("已記錄一筆位置資訊 , 詳細如下\n"+locList.get(locList.size()-1).toString());
            // 上傳資料至資料庫
        }
    }

    public ArrayList<Location> getLocList(){
        return locList;
    }
}
