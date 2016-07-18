package com.example.user.fich;

import java.util.HashMap;

/**
 * Created by huang on 2016/7/17.
 */
public class DBRequest {
    UserAction userAction;
    HashMap<String, String> map = new HashMap<String, String>();
    boolean ok = false;

    public DBRequest(UserAction ua){
        this.userAction = ua;
    }

    public void setPair(String key, String value){
        ok = true;
        map.put(key, value);
    }

}
