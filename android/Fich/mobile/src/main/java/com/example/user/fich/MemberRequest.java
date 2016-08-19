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