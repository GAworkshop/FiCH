package com.example.user.fich;

import android.os.Bundle;
import android.app.Activity;
import android.telephony.SmsManager;

public class SENDActivity extends Activity {

    PreferencesHelper prefHelper;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_send);

        prefHelper = new PreferencesHelper(this);
        String number = prefHelper.getString("c_phone");
        String text = "HELP!!!";
        SmsManager sms = SmsManager.getDefault();
        sms.sendTextMessage(number, null, text, null, null);
    }

}
