package com.example.user.fich;

import android.util.Log;

import org.json.JSONArray;
import org.json.JSONException;

/**
 * Created by huang on 2016/8/3.
 */
public class ConnectRequest {

    DBconnect conn;

    public ConnectRequest(DBRequest request){
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
                            Log.e("debugTTTT", "parse JSON Array " + result);
                            arr = new JSONArray(result);
                            return arr;
                                                        //implementation here................
                        }catch(JSONException e){
                            //returned value is not JSON, possibilly be a exit code
                            //by default, its returned value will be false
                            String code = "[false]";
                            Log.e("debugTTTT", "parse JSON Array Fail~");
                            Log.e("debugTTTT", result);

                            //if returnde a code, and it means true, then put it here and returned true

                            if(
                                    result.equals("300")            //user create success
                                 || result.equals("400")            //user update success
                                    )
                            {   //user update success
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