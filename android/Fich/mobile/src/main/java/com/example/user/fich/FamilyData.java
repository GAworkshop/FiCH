package com.example.user.fich;

/**
 * Created by user on 2016/9/16.
 */
public class FamilyData {

    private int id;
    private String user_name;
    private String heart;
    private String lng;
    private String lat;

    public FamilyData(String user_name, String heart, String lng, String lat){
        setUser_name(user_name);
        setHeart(heart);
        setLng(lng);
        setLat(lat);
    }

    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getUser_name() {
        return user_name;
    }

    public void setUser_name(String user_name) {
        this.user_name = user_name;
    }

    public String getHeart() {
        return heart;
    }

    public void setHeart(String heart) {
        this.heart = heart;
    }

    public String getLng() {
        return lng;
    }

    public void setLng(String lng) {
        this.lng = lng;
    }

    public String getLat() {
        return lat;
    }

    public void setLat(String lat) {
        this.lat = lat;
    }
}
