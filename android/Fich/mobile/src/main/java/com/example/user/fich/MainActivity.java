package com.example.user.fich;

import android.app.Activity;
import android.os.AsyncTask;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import com.google.android.gms.common.ConnectionResult;
import com.google.android.gms.common.api.GoogleApiClient;
import com.google.android.gms.wearable.DataApi;
import com.google.android.gms.wearable.DataEvent;
import com.google.android.gms.wearable.DataEventBuffer;
import com.google.android.gms.wearable.DataItem;
import com.google.android.gms.wearable.DataMap;
import com.google.android.gms.wearable.DataMapItem;
import com.google.android.gms.wearable.Wearable;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;

public class MainActivity extends Activity implements
        DataApi.DataListener,
        GoogleApiClient.ConnectionCallbacks,
        GoogleApiClient.OnConnectionFailedListener{

    private GoogleApiClient googleApiClient;
    private TextView acXTxv;
    private TextView acYTxv;
    private TextView acZTxv;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.fragment_sensor);

         googleApiClient = new GoogleApiClient.Builder(this)
                .addApi(Wearable.API)
                .addConnectionCallbacks(this)
                .addOnConnectionFailedListener(this)
                .build();

        acXTxv = (TextView)findViewById(R.id.acXTxv);
        acYTxv = (TextView)findViewById(R.id.acYTxv);
        acZTxv = (TextView)findViewById(R.id.acZTxv);

        Button uploadBtn = (Button)findViewById(R.id.uploadBtn);
        uploadBtn.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                new uploadDataTask().execute();
            }
        });
    }

    protected void onResume(){
        super.onResume();
        googleApiClient.connect();
    }

    protected void onPause(){
        super.onPause();
        googleApiClient.disconnect();
    }

    @Override
    public void onConnected(Bundle bundle) {
        Toast.makeText(MainActivity.this, "Connected", Toast.LENGTH_SHORT).show();
        Wearable.DataApi.addListener(googleApiClient, this);
    }

    @Override
    public void onConnectionSuspended(int i) {
        Toast.makeText(MainActivity.this,"Suspended",Toast.LENGTH_SHORT).show();
    }

    @Override
    public void onConnectionFailed(ConnectionResult connectionResult) {
        Toast.makeText(MainActivity.this,"Connection Failed",Toast.LENGTH_SHORT).show();
    }

    @Override
    public void onDataChanged(DataEventBuffer dataEventBuffer) {
        for (DataEvent event : dataEventBuffer) {
            if (event.getType() == DataEvent.TYPE_CHANGED) {
                // DataItem changed
                DataItem item = event.getDataItem();
                if (item.getUri().getPath().compareTo("/ga") == 0) {
                    DataMap dataMap = DataMapItem.fromDataItem(item).getDataMap();
                    String [] s = dataMap.getString("ac_value").split(",");
                    acXTxv.setText(s[0]);
                    acYTxv.setText(s[1]);
                    acZTxv.setText(s[2]);
                }
            } else if (event.getType() == DataEvent.TYPE_DELETED) {
                // DataItem deleted
            }
        }
    }


    class uploadDataTask extends AsyncTask{

        HttpURLConnection urlConnection;
        URL url;

        @Override
        protected Object doInBackground(String... args) {

            try {
                url = new URL("http://140.115.207.72/fich/api/XYZ.php");
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
                    in.close();
                    //urlConnection.connect();
                } finally {
                    if(urlConnection != null)
                        urlConnection.disconnect();
                }

            } catch (Exception e){

            } finally {

            }
            return null;
        }
    }
}