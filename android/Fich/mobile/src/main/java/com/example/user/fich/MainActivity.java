package com.example.user.fich;

import android.app.Activity;
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
                DBRequest r = new DBRequest(UserAction.DATA_UPLOAD);
                r.setPair("x", acXTxv.getText().toString());
                r.setPair("y", acYTxv.getText().toString());
                r.setPair("z", acZTxv.getText().toString());
                new DBconnect(r).execute();
                Toast.makeText(MainActivity.this,"Uploaded",Toast.LENGTH_SHORT).show();
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



}