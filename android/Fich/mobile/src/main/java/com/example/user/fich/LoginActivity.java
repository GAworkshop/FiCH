package com.example.user.fich;

import android.animation.Animator;
import android.animation.AnimatorListenerAdapter;
import android.annotation.TargetApi;
import android.app.Activity;
import android.app.LoaderManager.LoaderCallbacks;
import android.content.CursorLoader;
import android.content.Intent;
import android.content.Loader;
import android.content.pm.PackageManager;
import android.database.Cursor;
import android.net.Uri;
import android.os.AsyncTask;
import android.os.Build;
import android.os.Bundle;
import android.provider.ContactsContract;
import android.support.annotation.NonNull;
import android.support.design.widget.Snackbar;
import android.text.TextUtils;
import android.util.Log;
import android.view.KeyEvent;
import android.view.View;
import android.view.View.OnClickListener;
import android.view.inputmethod.EditorInfo;
import android.widget.ArrayAdapter;
import android.widget.AutoCompleteTextView;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONArray;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;

import static android.Manifest.permission.READ_CONTACTS;

//import android.support.design.widget.Snackbar;

//import android.support.design.widget.Snackbar;

/**
 * A login screen that offers login via email/password.
 */
public class LoginActivity extends Activity implements LoaderCallbacks<Cursor> {

    /**
     * Id to identity READ_CONTACTS permission request.
     *
     */
    //private static final String key = "fich";
    private static final int REQUEST_READ_CONTACTS = 0;
    //SharedPreferences pref = getApplication().getSharedPreferences(key, MODE_PRIVATE);
    //SharedPreferences.Editor prefEdit = pref.edit();
    PreferencesHelper prefHelpr;

    /**
     * A dummy authentication store containing known user names and passwords.
     * TODO: remove after connecting to a real authentication system.
     */
    private static final String[] DUMMY_CREDENTIALS = new String[]{
            "foo@example.com:hello", "bar@example.com:world"
    };
    /**
     * Keep track of the login task to ensure we can cancel it if requested.
     */
    private UserLoginTask mAuthTask = null;

    // UI references.
    private AutoCompleteTextView mEmailView;
    private EditText mPasswordView;
    private View mProgressView;
    private View mLoginFormView;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        setContentView(R.layout.activity_login);

        prefHelpr = new PreferencesHelper(this);

        // Set up the login form.
        mEmailView = (AutoCompleteTextView) findViewById(R.id.email);
        populateAutoComplete();

        mPasswordView = (EditText) findViewById(R.id.password);
        mPasswordView.setOnEditorActionListener(new TextView.OnEditorActionListener() {
            @Override
            public boolean onEditorAction(TextView textView, int id, KeyEvent keyEvent) {

                if (id == R.id.login || id == EditorInfo.IME_NULL) {
                    attemptLogin();
                    return true;
                }
                return false;
            }
        });

        Button mEmailSignInButton = (Button) findViewById(R.id.email_sign_in_button);
        mEmailSignInButton.setOnClickListener(new OnClickListener() {
            @Override
            public void onClick(View view) {
                attemptLogin();
            }
        });

        mLoginFormView = findViewById(R.id.login_form);
        mProgressView = findViewById(R.id.login_progress);

        Button goToRegisBtn = (Button)findViewById(R.id.goToRegisBtn);
        goToRegisBtn.setOnClickListener(new OnClickListener() {
            @Override
            public void onClick(View v) {
                startActivity(new Intent(LoginActivity.this, RegisterActivity.class));
            }
        });

    }

    private void populateAutoComplete() {
        if (!mayRequestContacts()) {
            return;
        }

        getLoaderManager().initLoader(0, null, this);
    }

    private boolean mayRequestContacts() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.M) {
            return true;
        }
        if (checkSelfPermission(READ_CONTACTS) == PackageManager.PERMISSION_GRANTED) {
            return true;
        }
        if (shouldShowRequestPermissionRationale(READ_CONTACTS)) {
            Snackbar.make(mEmailView, R.string.permission_rationale, Snackbar.LENGTH_INDEFINITE)
                    .setAction(android.R.string.ok, new OnClickListener() {
                        @Override
                        @TargetApi(Build.VERSION_CODES.M)
                        public void onClick(View v) {
                            requestPermissions(new String[]{READ_CONTACTS}, REQUEST_READ_CONTACTS);
                        }
                    });
        } else {
            requestPermissions(new String[]{READ_CONTACTS}, REQUEST_READ_CONTACTS);
        }
        return false;
    }

    /**
     * Callback received when a permissions request has been completed.
     */
    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions,
                                           @NonNull int[] grantResults) {
        if (requestCode == REQUEST_READ_CONTACTS) {
            if (grantResults.length == 1 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                populateAutoComplete();
            }
        }
    }


    /**
     * Attempts to sign in or register the account specified by the login form.
     * If there are form errors (invalid email, missing fields, etc.), the
     * errors are presented and no actual login attempt is made.
     */
    private void attemptLogin() {
        /*
        if (mAuthTask != null) {
            return;
        }
*/
        // Reset errors.
        mEmailView.setError(null);
        mPasswordView.setError(null);

        // Store values at the time of the login attempt.
        String email = mEmailView.getText().toString();
        final String password = mPasswordView.getText().toString();

        boolean cancel = false;
        View focusView = null;

        // Check for a valid password, if the user entered one.
        if (!TextUtils.isEmpty(password) && !isPasswordValid(password)) {
            mPasswordView.setError(getString(R.string.error_invalid_password));
            focusView = mPasswordView;
            cancel = true;
        }

        // Check for a valid email address.
        if (TextUtils.isEmpty(email)) {
            mEmailView.setError(getString(R.string.error_field_required));
            focusView = mEmailView;
            cancel = true;
        } else if (!isEmailValid(email)) {
            mEmailView.setError(getString(R.string.error_invalid_email));
            focusView = mEmailView;
            cancel = true;
        }

        if (cancel) {
            // There was an error; don't attempt login and focus the first
            // form field with an error.
            focusView.requestFocus();
        } else {
            // Show a progress spinner, and kick off a background task to
            // perform the user login attempt.
            showProgress(true);
            /*
            mAuthTask = new UserLoginTask(email, password);
            mAuthTask.execute((Void) null);
            */
            /*
            DBRequest request = new DBRequest(Action.USER_LOGIN);
            request.setPair("name", email);
            request.setPair("pass", password);
            System.out.println("CLICKED!!!!!!!!!!!!!!!!!!!!!!!!!!!");
            new DBconnect2(request).execute();
            */

            DBRequest dbRequest = new DBRequest(Action.USER_LOGIN);
            dbRequest.setPair("name",email);
            dbRequest.setPair("pass", password);
            MemberRequest m = new MemberRequest(dbRequest);
            m.execute(new DataCallback() {
                @Override
                public void onFinish(JSONArray jsonArray) {
                    try {

                        mAuthTask = null;
                        showProgress(false);
                        Log.e("debugTTTT", "onFinish");
                        Log.e("debugTTTT", ""+jsonArray.toString());
                        try {
                            if (!jsonArray.getBoolean(0)) {
                                Log.e("debugTTTT", "yes");
                                Toast.makeText(LoginActivity.this, "User Logged In!!", Toast.LENGTH_SHORT);
                                prefHelpr.storeData("isLogin", true);
                                prefHelpr.storeData(getResources().getString(R.string.UID), jsonArray.getInt(0));
                                prefHelpr.storeData(getResources().getString(R.string.name), jsonArray.getInt(4));
                                prefHelpr.storeData(getResources().getString(R.string.birthday), jsonArray.getInt(5));
                                prefHelpr.storeData(getResources().getString(R.string.history), jsonArray.getInt(6));
                                prefHelpr.storeData(getResources().getString(R.string.allergic), jsonArray.getInt(7));

                                //prefEdit.putBoolean("isLogin", true);
                                //prefEdit.commit();
                                //finish();
                                startActivity(new Intent(LoginActivity.this, MainActivity.class));
                                Log.e("TESTTEST","ADSASDASDASDASD");
                                finish();
                            } else {
                                Log.e("debugTTTT", "no");
                                mPasswordView.setError(getString(R.string.error_incorrect_password));
                                mPasswordView.requestFocus();
                                Toast.makeText(LoginActivity.this, "User Logged FAIL!!", Toast.LENGTH_SHORT);
                                prefHelpr.storeData("isLogin", false);
                                //prefEdit.putBoolean("isLogin", false);
                                //prefEdit.commit();
                            }
                        }catch(Exception e){
                            try {
                                Log.e("debugTTTT", "yes2");
                                //Toast.makeText(LoginActivity.this, "User Logged In!!", Toast.LENGTH_SHORT);
                                prefHelpr.storeData("isLogin", true);
//                                prefHelpr.storeData(getResources().getString(R.string.UID), jsonArray.getInt(0));
//                                prefHelpr.storeData(getResources().getString(R.string.name), jsonArray.getString(4));
//                                prefHelpr.storeData(getResources().getString(R.string.birthday), jsonArray.getString(5));
//                                prefHelpr.storeData(getResources().getString(R.string.history), jsonArray.getString(6));
//                                prefHelpr.storeData(getResources().getString(R.string.allergic), jsonArray.getString(7));

                                Log.e("", "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~");
                                //prefEdit.putBoolean("isLogin", true);
                                //prefEdit.commit();
                                //finish();
                                startActivity(new Intent(LoginActivity.this, MainActivity.class));
                                Log.e("TESTTEST", "ADSASDASDASDASD");
                                finish();
                            }catch (Exception e1){

                            }
                        }

                    }finally {

                    }
                }
            });
        }

    }

    private boolean isEmailValid(String email) {
        //TODO: Replace this with your own logic
        return email.contains("@");
    }

    private boolean isPasswordValid(String password) {
        //TODO: Replace this with your own logic
        return password.length() > 0;
    }

    /**
     * Shows the progress UI and hides the login form.
     */
    @TargetApi(Build.VERSION_CODES.HONEYCOMB_MR2)
    private void showProgress(final boolean show) {
        // On Honeycomb MR2 we have the ViewPropertyAnimator APIs, which allow
        // for very easy animations. If available, use these APIs to fade-in
        // the progress spinner.
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.HONEYCOMB_MR2) {
            int shortAnimTime = getResources().getInteger(android.R.integer.config_shortAnimTime);

            mLoginFormView.setVisibility(show ? View.GONE : View.VISIBLE);
            mLoginFormView.animate().setDuration(shortAnimTime).alpha(
                    show ? 0 : 1).setListener(new AnimatorListenerAdapter() {
                @Override
                public void onAnimationEnd(Animator animation) {
                    mLoginFormView.setVisibility(show ? View.GONE : View.VISIBLE);
                }
            });

            mProgressView.setVisibility(show ? View.VISIBLE : View.GONE);
            mProgressView.animate().setDuration(shortAnimTime).alpha(
                    show ? 1 : 0).setListener(new AnimatorListenerAdapter() {
                @Override
                public void onAnimationEnd(Animator animation) {
                    mProgressView.setVisibility(show ? View.VISIBLE : View.GONE);
                }
            });
        } else {
            // The ViewPropertyAnimator APIs are not available, so simply show
            // and hide the relevant UI components.
            mProgressView.setVisibility(show ? View.VISIBLE : View.GONE);
            mLoginFormView.setVisibility(show ? View.GONE : View.VISIBLE);
        }
    }

    @Override
    public Loader<Cursor> onCreateLoader(int i, Bundle bundle) {
        return new CursorLoader(this,
                // Retrieve data rows for the device user's 'profile' contact.
                Uri.withAppendedPath(ContactsContract.Profile.CONTENT_URI,
                        ContactsContract.Contacts.Data.CONTENT_DIRECTORY), ProfileQuery.PROJECTION,

                // Select only email addresses.
                ContactsContract.Contacts.Data.MIMETYPE +
                        " = ?", new String[]{ContactsContract.CommonDataKinds.Email
                .CONTENT_ITEM_TYPE},

                // Show primary email addresses first. Note that there won't be
                // a primary email address if the user hasn't specified one.
                ContactsContract.Contacts.Data.IS_PRIMARY + " DESC");
    }

    @Override
    public void onLoadFinished(Loader<Cursor> cursorLoader, Cursor cursor) {
        List<String> emails = new ArrayList<>();
        cursor.moveToFirst();
        while (!cursor.isAfterLast()) {
            emails.add(cursor.getString(ProfileQuery.ADDRESS));
            cursor.moveToNext();
        }

        addEmailsToAutoComplete(emails);
    }

    @Override
    public void onLoaderReset(Loader<Cursor> cursorLoader) {

    }

    private void addEmailsToAutoComplete(List<String> emailAddressCollection) {
        //Create adapter to tell the AutoCompleteTextView what to show in its dropdown list.
        ArrayAdapter<String> adapter =
                new ArrayAdapter<>(LoginActivity.this,
                        android.R.layout.simple_dropdown_item_1line, emailAddressCollection);

        mEmailView.setAdapter(adapter);
    }


    private interface ProfileQuery {
        String[] PROJECTION = {
                ContactsContract.CommonDataKinds.Email.ADDRESS,
                ContactsContract.CommonDataKinds.Email.IS_PRIMARY,
        };

        int ADDRESS = 0;
        int IS_PRIMARY = 1;
    }

    /**
     * Represents an asynchronous login/registration task used to authenticate
     * the user.
     */
    public class UserLoginTask extends AsyncTask<Void, Void, Boolean> {

        private final String mEmail;
        private final String mPassword;

        UserLoginTask(String email, String password) {
            mEmail = email;
            mPassword = password;
        }

        @Override
        protected Boolean doInBackground(Void... params) {
            // TODO: attempt authentication against a network service.

            try {
                // Simulate network access.
                Thread.sleep(2000);
            } catch (InterruptedException e) {
                return false;
            }

            for (String credential : DUMMY_CREDENTIALS) {
                String[] pieces = credential.split(":");
                if (pieces[0].equals(mEmail)) {
                    // Account exists, return true if the password matches.
                    return pieces[1].equals(mPassword);
                }
            }

            // TODO: register the new account here.
            return true;
        }

        @Override
        protected void onPostExecute(final Boolean success) {
            mAuthTask = null;
            showProgress(false);

            if (success) {
                finish();
            } else {
                mPasswordView.setError(getString(R.string.error_incorrect_password));
                mPasswordView.requestFocus();
            }
        }

        @Override
        protected void onCancelled() {
            mAuthTask = null;
            showProgress(false);
        }
    }



    /**
     * Created by huang on 2016/6/20.
     */
    class DBconnect2 extends AsyncTask<String, String, Boolean>{

        HttpURLConnection urlConnection;
        URL url;
        Action action;
        HashMap<String, String> data;

        public DBconnect2(DBRequest request){
            System.out.println("DB CONNECT CREATED!!!!!!!!!!!!!!!!!!!!!!!!!!!");
            System.out.println(request.action.toString());
            this.action = request.action;
            this.data = request.data;
        }

        protected String parseData(HashMap<String, String> data){
            StringBuilder params = new StringBuilder("");
            try {
                params.append("action=" + action.toString());
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
        protected Boolean doInBackground(String... args) {


            try {
                Thread.sleep(2000);
                String parm = "x=0.123&y=0.123&z=0.123";
                parm = parseData(data);

                url = new URL("http://140.115.207.72/fich/api/member.php");
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

                    if(urlConnection != null)
                        urlConnection.disconnect();

                    if(response.contains("200")){
                        return true;
                    }else{
                        return false;
                    }
                } finally {



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
        protected void onPostExecute(final Boolean success) {
            mAuthTask = null;
            showProgress(false);

            if (success) {
                Toast.makeText(LoginActivity.this, "User Logged In!!", Toast.LENGTH_SHORT);
                finish();
            } else {
                mPasswordView.setError(getString(R.string.error_incorrect_password));
                mPasswordView.requestFocus();
                Toast.makeText(LoginActivity.this, "User Logged FAIL!!", Toast.LENGTH_SHORT);
            }
        }


    }

}

