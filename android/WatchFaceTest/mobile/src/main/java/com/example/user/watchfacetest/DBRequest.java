package com.example.user.watchfacetest;

import java.util.HashMap;

/**
 * Created by huang on 2016/7/17.
 */
public class DBRequest {
    Action action;
    HashMap<String, String> map = new HashMap<String, String>();
    boolean ready = false;

    public DBRequest(Action ua){
        this.action = ua;
    }

    public void setPair(String key, String value){
        ready = true;
        map.put(key, value);
    }

}
