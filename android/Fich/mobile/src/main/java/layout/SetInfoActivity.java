package layout;

import android.app.DatePickerDialog;
import android.app.Dialog;
import android.app.TimePickerDialog;
import android.os.Bundle;
import android.support.v4.app.DialogFragment;
import android.support.v4.app.FragmentManager;
import android.support.v7.app.AppCompatActivity;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.DatePicker;
import android.widget.EditText;
import android.widget.TimePicker;

import com.example.user.fich.PreferencesHelper;
import com.example.user.fich.R;

import java.util.Calendar;

public class SetInfoActivity extends AppCompatActivity {

    private static Button btn_birthday;
    private EditText et_name;
    private EditText et_blood;
    private EditText et_history;
    private EditText et_allergic;

    private static int mYear, mMonth, mDay, mHour, mMinute;

    PreferencesHelper prefHelpr;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_set_info);
        prefHelpr = new PreferencesHelper(this);
        btn_birthday = (Button) findViewById(R.id.btn_birthday);
        et_name = (EditText) findViewById(R.id.et_person_name);
        et_blood = (EditText) findViewById(R.id.et_blood);
        et_history = (EditText) findViewById(R.id.et_history);
        et_allergic = (EditText) findViewById(R.id.et_allergic);
        et_name.requestFocus();
        showNow();
    }

    private static void showNow() {
        Calendar calendar = Calendar.getInstance();
        mYear = calendar.get(Calendar.YEAR);
        mMonth = calendar.get(Calendar.MONTH);
        mDay = calendar.get(Calendar.DAY_OF_MONTH);
        mHour = calendar.get(Calendar.HOUR_OF_DAY);
        mMinute = calendar.get(Calendar.MINUTE);
        //updateDisplay();
    }

    public void submit(View view){

        et_name.setError(null);
        et_blood.setError(null);
        btn_birthday.setError(null);

        String name = et_name.getText().toString();
        String birthday = btn_birthday.getText().toString();
        String blood = et_blood.getText().toString();

        if(name.isEmpty()){
            et_name.setError(getString(R.string.error_name));
            et_name.requestFocus();
            return;
        }

        if(birthday.equals(getString(R.string.birthday_hint))){
            btn_birthday.setError(getString(R.string.error_birthday));
            btn_birthday.requestFocus();
            return;
        }

        if(blood.isEmpty()){
            et_blood.setError(getString(R.string.error_blood));
            et_blood.requestFocus();
            return;
        }

        prefHelpr.storeData(getResources().getString(R.string.name), name);
        prefHelpr.storeData(getResources().getString(R.string.lbl_birthday), birthday);
        prefHelpr.storeData(getResources().getString(R.string.lbl_blood), blood);
        prefHelpr.storeData(getResources().getString(R.string.lbl_history), et_history.getText().toString());
        prefHelpr.storeData(getResources().getString(R.string.lbl_allergic), et_allergic.getText().toString());
        prefHelpr.storeData(getResources().getString(R.string.is_setting_done), true);

        this.finish();
    }

    @Override
    public void onBackPressed() {
        super.onBackPressed();
    }

    @Override
    protected void onDestroy(){
        super.onDestroy();
        Log.e("~~DEBUG~~", "info  onDestroy");
        //toast.cancel();

    }

    @Override
    public void onResume(){
        super.onResume();
        Log.e("~~DEBUG~~", "info onResume");
        //if not set self info yet, go back to set
    }

    @Override
    public void onPause(){
        super.onPause();
        Log.e("~~DEBUG~~", "info onPause");
    }

    public void onDateClick(View view) {
        DatePickerDialogFragment datePickerFragment = new DatePickerDialogFragment();
        FragmentManager fm = getSupportFragmentManager();
        datePickerFragment.show(fm, "datePicker");
    }

    public void onTimeClick(View view) {
        TimePickerDialogFragment timePickerFragment = new TimePickerDialogFragment();
        FragmentManager fm = getSupportFragmentManager();
        timePickerFragment.show(fm, "timePicker");
    }

    public static class DatePickerDialogFragment extends DialogFragment implements
            DatePickerDialog.OnDateSetListener {

        @Override
        public Dialog onCreateDialog(Bundle savedInstanceState) {
            return new DatePickerDialog(
                    getActivity(), this, mYear, mMonth, mDay);
        }

        @Override
        public void onDateSet(DatePicker view, int year, int month, int day) {
            mYear = year;
            mMonth = month;
            mDay = day;
            updateDisplay();
        }
    }

    public static class TimePickerDialogFragment extends DialogFragment implements
            TimePickerDialog.OnTimeSetListener {
        @Override
        public Dialog onCreateDialog(Bundle savedInstanceState) {
            return new TimePickerDialog(
                    getActivity(), this, mHour, mMinute, false);
        }

        @Override
        public void onTimeSet(TimePicker view, int hourOfDay, int minute) {
            mHour = hourOfDay;
            mMinute = minute;
            updateDisplay();
        }
    }

    private static void updateDisplay() {
        btn_birthday.setText(new StringBuilder().append(mYear).append("-")
                .append(pad(mMonth + 1)).append("-").append(pad(mDay)));
    }

    private static String pad(int number) {
        if (number >= 10)
            return String.valueOf(number);
        else
            return "0" + String.valueOf(number);
    }

}
