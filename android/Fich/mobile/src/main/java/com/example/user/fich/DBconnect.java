package com.example.user.fich;

import android.os.AsyncTask;
import android.util.Log;

import org.json.JSONArray;
import org.json.JSONException;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.util.HashMap;

/**
 * Created by huang on 2016/6/20.
 */
public class DBconnect extends AsyncTask<String, String, String>{

    HttpURLConnection urlConnection;
    URL url;
    //Action action;
    //HashMap<String, String> data;
    DBRequest dbRequest;
    ParseFunction parseFunction;
    DataCallback dataCallback;
    String jsonString;          //returned data by server

    public DBconnect(DBRequest request){
        //this.action = request.action;
        //this.data = request.data;
        this.dbRequest = request;
    }

    public void setParseFunction(ParseFunction parseFunction, DataCallback dataCallback){
        this.parseFunction = parseFunction;
        this.dataCallback = dataCallback;
    }

    protected String parseData(HashMap<String, String> data){
        StringBuilder params = new StringBuilder("");
        try {
            params.append("action=" + this.dbRequest.action.toString());
            for (String key : data.keySet()) {
                params.append("&" + key + "=");
                params.append(URLEncoder.encode(data.get(key), "UTF-8"));
            }
        }catch (Exception e){

        }
        return params.toString();
    }

    @Override
    protected void onPreExecute() {
        super.onPreExecute();
    }

    /**
     * Creating product
     * */
    protected String doInBackground(String... args) {

        Log.e("debugTTTT", "doInBackground");
        Log.e("debugTTTT", this.dbRequest.url);

        try {
            Thread.sleep(1500);
            String param = "x=0.123&y=0.123&z=0.123";
            param = parseData(this.dbRequest.data);
            Log.e("debugTTTT", param);

            url = new URL(this.dbRequest.url);
            urlConnection = (HttpURLConnection) url.openConnection();

            urlConnection.setRequestMethod("POST");
            urlConnection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
            urlConnection.setDoInput(true);
            urlConnection.setDoOutput(true);

            urlConnection.setReadTimeout(10000);
            urlConnection.setConnectTimeout(15000);

            urlConnection.setChunkedStreamingMode(0);
            urlConnection.setUseCaches(false);

            urlConnection.setRequestProperty("Content-Length", String.valueOf(param.length()));

            try {
                OutputStream os = urlConnection.getOutputStream();
                BufferedWriter out = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"));

                out.write(param);
                out.flush();
                out.close();
                os.close();


                urlConnection.connect();

                InputStream is = urlConnection.getInputStream();
                BufferedReader in = new BufferedReader(new InputStreamReader(is));
                String response = "", line;
                while ((line=in.readLine()) != null) {
                    response+=line;
                }
                System.out.print("~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"+response);
                jsonString = response;
                if(response.contains("200")){

                }else{

                }
                in.close();

                //urlConnection.connect();

            } finally {
                if(urlConnection != null)
                    urlConnection.disconnect();
            }



        } catch (Exception e){

        }
        finally {

        }

        return null;

    }

    public JSONArray parseResult(String result) {
        Log.e("debugTTTT", "parse Start!");
        JSONArray arr;
        try{
            //if result is JSON array, returns it
            Log.e("debugTTTT", "parse JSON Array");
            arr = new JSONArray(result);
            return arr;
        }catch(JSONException e){
            //returned value is not JSON, possibilly be a exit code
            String code = "[false]";
            Log.e("debugTTTT", "parse JSON Array Fail~");
            Log.e("debugTTTT", result);

            if(result.equals("200")){
                //code = "[true]";
            }
            try {
                Log.e("~~DEBUG~~", "result: " + result);
                switch (Integer.parseInt(result)){
                    case 100:
                    case 200 :
                    case 300:
                        code = "[true]";
                        break;
                    default:
                        code = "[false]";

                }

                return new JSONArray(code);

            }catch (Exception e2){
                return null;
            }
        }

    }

    /**
     * After completing background task Dismiss the progress dialog
     * **/
    protected void onPostExecute(String file_url) {
        // dismiss the dialog once done
        Log.e("debugTTTT", "onPostExecute");
        Log.e("debugTTTT", jsonString);
        try {
            this.dataCallback.onFinish(this.parseFunction.parse(jsonString));
        }catch (Exception e){

        }

    }


}
