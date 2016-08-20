package com.example.user.fich;

import android.app.Activity;
import android.os.Bundle;
import android.text.TextUtils;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;

public class RegisterActivity extends Activity {
    String email;
    String password;
    String phone;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        final EditText email_et = (EditText)findViewById(R.id.email_regis);
        final EditText password_et = (EditText)findViewById(R.id.password_regis);
        final EditText phone_et = (EditText)findViewById(R.id.phone_regis);

        Button cancelBtn = (Button)findViewById(R.id.cancelBtn);
        cancelBtn.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                finish();
            }
        });
        Button registerBtn = (Button)findViewById(R.id.registerBtn);
        registerBtn.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                email = email_et.getText().toString().trim();
                password = password_et.getText().toString().trim();
                phone = phone_et.getText().toString().trim();
                if((TextUtils.isEmpty(email))){
                    email_et.setError(getString(R.string.error_field_required));
                }else if(!email.contains("@")){
                    email_et.setError(getString(R.string.error_invalid_email));
                }else if(password.length() == 0){
                    password_et.setError(getString(R.string.error_field_required));
                }else if(phone.length() == 0){
                    phone_et.setError(getString(R.string.error_field_required));
                }else{

                }
            }
        });
    }
}
