package com.example.user.fich;

import java.text.SimpleDateFormat;
import java.util.Date;

/**
 * Created by user on 2016/8/13.
 */
public class Location {

    private double longitude;
    private double latitude;
    private long time;

    public Location(double longitude, double latitude, long time){
        setLongitude(longitude);
        setLatitude(latitude);
        setTime(time);
    }

    public double getLongitude() {
        return longitude;
    }

    public void setLongitude(double longitude) {
        this.longitude = longitude;
    }

    public double getLatitude() {
        return latitude;
    }

    public void setLatitude(double latitude) {
        this.latitude = latitude;
    }

    public long getTime() {
        return time;
    }

    public void setTime(long time) {
        this.time = time;
    }

    public String toString(){
        return String.format("經度:%f\n緯度%f\n時間:%s",getLongitude(),getLatitude(),new SimpleDateFormat("yyyy年MM月dd日 HH:mm").format(new Date(getTime())));
    }
}
