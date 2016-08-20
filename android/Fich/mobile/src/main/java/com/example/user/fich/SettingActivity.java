package com.example.user.fich;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.ComponentName;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.ServiceConnection;
import android.os.Bundle;
import android.os.IBinder;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.ListAdapter;
import android.widget.ListView;
import android.widget.Toast;

public class SettingActivity extends Activity {

    private String [] setting = { "定位間隔時間","心律間隔時間" };
    ListView lv;
    int period = 5;
    private LocService locService;
    int pos;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_setting);

        lv = (ListView)findViewById(R.id.setting_listView);
        ListAdapter mAdapter = new ArrayAdapter<String>(this,android.R.layout.simple_list_item_1,setting);
        lv.setAdapter(mAdapter);
        lv.setOnItemClickListener(new AdapterView.OnItemClickListener(){
            @Override
            public void onItemClick(AdapterView<?> parent, View view, int position, long id) {
                if(position == 0 || position == 1) {        // the selected item of setting listview
                    String title = "";                      // position 0 : Location Recording Period
                    if(position == 0){                      // position 1 : Heart Rate Recording Period
                        title = "選擇每隔多少時間存取位置資訊";
                    }else if(position == 1){
                        title = "選擇每隔多少分鐘存取心律資訊";
                    }
                    pos = position;
                    new AlertDialog.Builder(SettingActivity.this)
                            .setTitle(title)
                            .setItems(R.array.setting_period_dialog, new DialogInterface.OnClickListener() {
                                @Override
                                public void onClick(DialogInterface dialog, int which) {
                                    switch (which){
                                        case 0:
                                            period = 5;
                                            break;
                                        case 1:
                                            period = 10;
                                            break;
                                        case 2:
                                            period = 20;
                                            break;
                                        case 3:
                                            period = 30;
                                            break;
                                        case 4:
                                            period = 60;
                                            break;
                                    }
                                    ServiceConnection setTaskPeriodLocServiceCon = new ServiceConnection() {
                                        @Override
                                        public void onServiceConnected(ComponentName name, IBinder binder) {
                                            locService = ((LocService.ServiceBinder) binder).getService();
                                            if(pos == 0) {
                                                locService.setTaskPeriod(period);
                                            }else if(pos == 1){
                                                locService.setHRPeriod(period);
                                            }
                                            unbindService(this);
                                            if(pos == 0){
                                                Toast.makeText(SettingActivity.this, "已更改定位間隔時間為 " + period + " 分鐘", Toast.LENGTH_LONG).show();
                                            }else if(pos == 1){
                                                Toast.makeText(SettingActivity.this, "已更改心律間隔時間為 " + period + " 分鐘", Toast.LENGTH_LONG).show();
                                            }
                                        }
                                        @Override
                                        public void onServiceDisconnected(ComponentName name) {
                                            locService = null;
                                        }
                                    };
                                    Intent intent = new Intent(SettingActivity.this, LocService.class);
                                    bindService(intent, setTaskPeriodLocServiceCon, Context.BIND_AUTO_CREATE);
                                }
                            }).show();
                }
            }
        });
    }

}
