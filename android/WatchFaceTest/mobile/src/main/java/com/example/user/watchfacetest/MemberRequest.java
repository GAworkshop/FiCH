package com.example.user.watchfacetest;

import android.util.Log;

import org.json.JSONArray;
import org.json.JSONException;

/**
 * Created by huang on 2016/8/3.
 */
public class MemberRequest {

    DBconnect conn;

    public MemberRequest(DBRequest request){
        conn = new DBconnect(request);

        String s = "[[\"5\",\"1\",\"1\",\"1\",\"1\",\"1\",\"1\",\"2016-08-02 21:30:36\"],[\"6\",\"2\",\"2\",\"2\",\"2\",\"2\",\"2\",\"2016-08-02 21:30:36\"],[\"7\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"2016-08-02 21:30:36\"],[\"2\",\"1\",\"1\",\"1\",\"1\",\"1\",\"1\",\"2016-08-02 21:30:07\"],[\"3\",\"2\",\"2\",\"2\",\"2\",\"2\",\"2\",\"2016-08-02 21:30:07\"],[\"4\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"2016-08-02 21:30:07\"],[\"1\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"2016-08-02 21:08:23\"]]";
        JSONArray arr = new JSONArray();
        try{
            arr = new JSONArray(s.toString());
            for (int i = 0; i < arr.length(); i++) {
                for (int j = 0; j < arr.getJSONArray(i).length(); j++) {
                    Log.e("(" + i + "," + j + ")", "" + arr.getJSONArray(i).get(j).toString());
                }
            }
        }catch(JSONException e){
            e.printStackTrace();
        }
        System.out.print("$$$$$$$$$$$$$$$$$$$$$$$"+arr.toString());
    }

    public void execute(DataCallback dataCallback){
        conn.setParseFunction(
                new ParseFunction() {
                    @Override
                    public JSONArray parse(String result) {
                        JSONArray arr = new JSONArray();
                        try{
                            arr = new JSONArray(result);
                                                        //implementation here................
                        }catch(JSONException e){
                            e.printStackTrace();
                        }
                        return arr;
                    }
                }, dataCallback
        );

        conn.execute();
    }


}