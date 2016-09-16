package com.example.user.fich;

import android.util.Log;

import java.util.HashMap;

/**
 * Created by huang on 2016/7/17.
 */
public class DBRequest {
    Action action;
    HashMap<String, String> data = new HashMap<String, String>();
    String url = "";
    boolean ready = false;
    private static final String domain = "http://140.115.80.231/fich/api";

    public DBRequest(Action ua){
        this.action = ua;
        Log.e("debugTTTT URL - BEFORE", ua.toString());
        switch(ua){
            case CREATE_USER:
            case DELETE_USER:
            case UPDATE_USER:
            case SELECT_USER:
            case USER_LOGIN:
                this.url = domain + "/Member.php";
                break;
            case SENSOR_SAVE:
            case SENSOR_SELECT:
                this.url = domain + "/SensorData.php";
                break;
            case LOC_SAVE:
            case HEART_SAVE:
            case LOC_SELECT:
            case HEART_SELECT:
                this.url = domain + "/LocHeart.php";
                break;
            case CHECK_MATCH:
            case AUTH:
            case MATCH_REQUEST:
            case LOOK_FAMILY_DATA:
                this.url = domain + "/Matches.php";
                break;
            default:
                this.url = "GGGGGGGGGGGGGGGGGGGGGGGGG";
        }
        Log.e("debugTTTT - GGGG", url);
    }

    public void setPair(String key, String value){
        ready = true;
        data.put(key, value);
    }

}
