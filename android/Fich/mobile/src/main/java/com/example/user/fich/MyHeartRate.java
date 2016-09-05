package com.example.user.fich;

import java.text.SimpleDateFormat;
import java.util.Date;

/**
 * Created by Ga on 2016/8/20.
 */
public class MyHeartRate {

    private int heartRate;
    private long time;

    public MyHeartRate(int hr, long time){
        setHeartRate(hr);
        setTime(time);
    }

    public int getHeartRate() {
        return heartRate;
    }

    public void setHeartRate(int heartRate) {
        this.heartRate = heartRate;
    }

    public long getUnixTime() {
        return time;
    }

    public String getDateTime() {
        return new SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(new Date(getUnixTime()));
    }

    public void setTime(long time) {
        this.time = time;
    }

    public String toString(){
        return String.format("%s Heart rate : %d",getDateTime(),getHeartRate());
    }
}
