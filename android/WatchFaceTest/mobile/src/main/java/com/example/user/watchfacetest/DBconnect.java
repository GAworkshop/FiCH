package com.example.user.watchfacetest;

import android.os.AsyncTask;
import android.util.Log;

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
    Action action;
    HashMap<String, String> data;
    ParseFunction parseFunction;
    DataCallback dataCallback;
    String jsonString;          //returned data by server

    public DBconnect(DBRequest request){
        this.action = request.action;
        this.data = request.map;
    }

    public void setParseFunction(ParseFunction parseFunction, DataCallback dataCallback){
        this.parseFunction = parseFunction;
        this.dataCallback = dataCallback;
    }

    protected String parseData(HashMap<String, String> data){
        StringBuilder params = new StringBuilder("");
        try {
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



        try {
            String parm = "x=0.123&y=0.123&z=0.123";
            parm = parseData(data);

            url = new URL("http://140.115.207.72/fich/api/SensorData.php");
            urlConnection = (HttpURLConnection) url.openConnection();

            urlConnection.setRequestMethod("POST");
            urlConnection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
            urlConnection.setDoInput(true);
            urlConnection.setDoOutput(true);

            urlConnection.setReadTimeout(10000);
            urlConnection.setConnectTimeout(15000);

            urlConnection.setChunkedStreamingMode(0);
            urlConnection.setUseCaches(false);

            urlConnection.setRequestProperty("Content-Length", String.valueOf(parm.length()));

            try {
                OutputStream os = urlConnection.getOutputStream();
                BufferedWriter out = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"));

                out.write(parm);
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
                Log.e("jsonStringggggggggg",jsonString);
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

    /**
     * After completing background task Dismiss the progress dialog
     * **/
    protected void onPostExecute(String file_url) {
        // dismiss the dialog once done

        try {
            this.dataCallback.onFinish(this.parseFunction.parse(jsonString));
        }catch (Exception e){

        }

    }


}
