package com.example.user.fich;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.Context;
import android.content.DialogInterface;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.BaseAdapter;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONArray;

import java.util.ArrayList;

public class RelationshipActivity extends Activity {

    ArrayList<Contact> rela_list = new ArrayList<>();
    ListView relaListView;
    RelationshipAdapter relaAdapter;
    PreferencesHelper prefHelper;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_relationship);

        //接收待確認資料 , 將資料放進rela_list裡
        receiveData();

        relaListView = (ListView)findViewById(R.id.relaListView);
        relaAdapter = new RelationshipAdapter(this);
        relaListView.setAdapter(relaAdapter);

        relaListView.setOnItemClickListener(new AdapterView.OnItemClickListener() {
            @Override
            public void onItemClick(AdapterView<?> parent, View view, final int position, long id) {
                new AlertDialog.Builder(RelationshipActivity.this)
                        .setTitle("關係確認")
                        .setMessage("確認與 " + rela_list.get(position).getName() + " 為家庭成員關係嗎")
                        .setPositiveButton("確認", new DialogInterface.OnClickListener() {
                            @Override
                            public void onClick(DialogInterface dialog, int which) {
                                //送出確認請求
                                confirmReq(position);
                                //確認後在列表中移除該項
                                rela_list.remove(position);
                                relaAdapter.notifyDataSetChanged();
                                //列表項目為0後進入主畫面
                                if(rela_list.size() == 0){
                                    finish();
                                }
                            }
                        })
                        .setNegativeButton("拒絕", new DialogInterface.OnClickListener() {
                            @Override
                            public void onClick(DialogInterface dialog, int which) {
                                //拒絕後在列表中移除該項
                                rela_list.remove(position);
                                relaAdapter.notifyDataSetChanged();
                                //列表項目為0後進入主畫面
                                if(rela_list.size() == 0){
                                    finish();
                                }
                            }
                        })
                        .show();
            }
        });
    }

    public void receiveData(){
        Bundle bundle = this.getIntent().getExtras();
        try {
            JSONArray jsonArray = new JSONArray(bundle.getString("relData"));
            for (int i =0; i < jsonArray.length(); i++){
                JSONArray dataRow = jsonArray.optJSONArray(i);
                Contact c = new Contact(dataRow.getString(1), dataRow.getString(2));
                c.setId(dataRow.getInt(0));
                rela_list.add(c);
            }

        }catch (Exception e){

        }

    }

    public void confirmReq(int position){
        DBRequest dbRequest = new DBRequest(Action.AUTH);
        dbRequest.setPair("wear_id", prefHelper.getInt(getResources().getString(R.string.UID))+"" );
        dbRequest.setPair("family_id", rela_list.get(position).getId()+"");
        ConnectRequest m = new ConnectRequest(dbRequest);
        m.execute(new DataCallback() {
            @Override
            public void onFinish(JSONArray jsonArray) {
                try {
                    Log.e("~~~~~~~~~~~~", ">>>>>>>>>>>>>>><<<<<<<<<<<<<<<<<<<<<"+jsonArray.optBoolean(0));
                    if(jsonArray.getBoolean(0)){
                        Toast.makeText(RelationshipActivity.this, "success", Toast.LENGTH_SHORT);

                    }else {
                        Toast.makeText(RelationshipActivity.this, "connection lost", Toast.LENGTH_SHORT);
                    }
                }catch (Exception e){

                }

            }
        });
    }

    private class RelationshipAdapter extends BaseAdapter {
        private LayoutInflater layoutInflater;

        public RelationshipAdapter(Context context){
            layoutInflater = LayoutInflater.from(context);
        }

        @Override
        public int getCount() {
            return rela_list.size();
        }

        @Override
        public Object getItem(int position) {
            return rela_list.get(position);
        }

        @Override
        public long getItemId(int position) {
            return rela_list.get(position).getId();
        }

        @Override
        public View getView(int position, View convertView, ViewGroup parent) {
            if (convertView == null) {
                convertView = layoutInflater.inflate(R.layout.contactlistview_item, parent, false);
            }

            Contact contact = rela_list.get(position);
            TextView nameTv = (TextView)convertView.findViewById(R.id.contactNameTv);
            nameTv.setText(contact.getName());
            TextView phoneTv = (TextView)convertView.findViewById(R.id.contactPhoneTv);
            phoneTv.setText(contact.getPhone());

            return convertView;
        }
    }
}
