package com.example.user.fich;

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
/*
        String s = "[[\"5\",\"1\",\"1\",\"1\",\"1\",\"1\",\"1\",\"2016-08-02 21:30:36\"],[\"6\",\"2\",\"2\",\"2\",\"2\",\"2\",\"2\",\"2016-08-02 21:30:36\"],[\"7\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"2016-08-02 21:30:36\"],[\"2\",\"1\",\"1\",\"1\",\"1\",\"1\",\"1\",\"2016-08-02 21:30:07\"],[\"3\",\"2\",\"2\",\"2\",\"2\",\"2\",\"2\",\"2016-08-02 21:30:07\"],[\"4\",\"3\",\"3\",\"3\",\"3\",\"3\",\"3\",\"2016-08-02 21:30:07\"],[\"1\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"2016-08-02 21:08:23\"]]";
        JSONArray arr = new JSONArray();
        try{
            arr = new JSONArray(s.toString());
            for (int i = 0; i < arr.length(); i++) {
                for (int j = 0; j < arr.getJSONArray(i).length(); j++) {
                    //Log.e("(" + i + "," + j + ")", "" + arr.getJSONArray(i).get(j).toString());
                }
            }
        }catch(JSONException e){


        }

        Log.e("$$$$$$$$$$$$$$$$$$$$$$$",""+arr.toString());*/
    }

    public void execute(DataCallback dataCallback){
        conn.setParseFunction(
                new ParseFunction() {
                    @Override
                    public JSONArray parse(String result) {
                        Log.e("debugTTTT", "parse Start!");
                        JSONArray arr = new JSONArray();
                        try{
                            Log.e("debugTTTT", "parse JSON Array");
                            arr = new JSONArray(result);
                            return arr;
                                                        //implementation here................
                        }catch(JSONException e){
                            //returned value is not JSON, possibilly be a exit code
                            String code = "[false]";
                            Log.e("debugTTTT", "parse JSON Array Fail~");
                            Log.e("debugTTTT", result);

                            if(result.equals("200")){
                                code = "[true]";
                            }

                            try {
                                return new JSONArray(code);
                            }catch (Exception e2){
                                return null;
                            }
                        }

                    }
                }, dataCallback
        );

        conn.execute();
    }



}