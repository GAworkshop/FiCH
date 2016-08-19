/*
 * Copyright (C) 2014 The Android Open Source Project
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

package com.example.user.watchfacetest;

import android.app.Service;
import android.appwidget.AppWidgetManager;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.res.Resources;
import android.graphics.Canvas;
import android.graphics.Color;
import android.graphics.Paint;
import android.graphics.Point;
import android.graphics.Rect;
import android.graphics.Typeface;
import android.hardware.Sensor;
import android.hardware.SensorEvent;
import android.hardware.SensorEventListener;
import android.hardware.SensorManager;
import android.os.Bundle;
import android.os.Handler;
import android.os.Message;
import android.os.Vibrator;
import android.provider.Settings;
import android.support.annotation.NonNull;
import android.support.annotation.Nullable;
import android.support.wearable.watchface.CanvasWatchFaceService;
import android.support.wearable.watchface.WatchFaceStyle;
import android.text.format.Time;
import android.util.DisplayMetrics;
import android.util.Log;
import android.view.Display;
import android.view.LayoutInflater;
import android.view.SurfaceHolder;
import android.view.View;
import android.view.WindowInsets;
import android.view.WindowManager;
import android.widget.AnalogClock;
import android.widget.TextView;
import android.widget.Toast;

import com.google.android.gms.common.ConnectionResult;
import com.google.android.gms.common.api.GoogleApiClient;
import com.google.android.gms.wearable.DataApi;
import com.google.android.gms.wearable.DataEventBuffer;
import com.google.android.gms.wearable.PutDataMapRequest;
import com.google.android.gms.wearable.PutDataRequest;
import com.google.android.gms.wearable.Wearable;

import java.lang.ref.WeakReference;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.TimeZone;
import java.util.Timer;
import java.util.TimerTask;
import java.util.concurrent.TimeUnit;

/**
 * Digital watch face with seconds. In ambient mode, the seconds aren't displayed. On devices with
 * low-bit ambient mode, the text is drawn without anti-aliasing in ambient mode.
 */
public class MyWatchFace extends CanvasWatchFaceService{
    private static final Typeface NORMAL_TYPEFACE =
            Typeface.create(Typeface.SANS_SERIF, Typeface.NORMAL);

    /**
     * Update rate in milliseconds for interactive mode. We update once a second since seconds are
     * displayed in interactive mode.
     */
    private static final long INTERACTIVE_UPDATE_RATE_MS = TimeUnit.SECONDS.toMillis(1);

    /**
     * Handler message id for updating the time periodically in interactive mode.
     */
    private static final int MSG_UPDATE_TIME = 0;

    @Override
    public Engine onCreateEngine() {
        return new Engine();
    }

    private static class EngineHandler extends Handler {
        private final WeakReference<MyWatchFace.Engine> mWeakReference;

        public EngineHandler(MyWatchFace.Engine reference) {
            mWeakReference = new WeakReference<>(reference);
        }

        @Override
        public void handleMessage(Message msg) {
            MyWatchFace.Engine engine = mWeakReference.get();
            if (engine != null) {
                switch (msg.what) {
                    case MSG_UPDATE_TIME:
                        engine.handleUpdateTimeMessage();
                        break;
                }
            }
        }
    }

    private class Engine extends CanvasWatchFaceService.Engine implements
            DataApi.DataListener,
            GoogleApiClient.ConnectionCallbacks,
            GoogleApiClient.OnConnectionFailedListener,
            SensorEventListener{
        final Handler mUpdateTimeHandler = new EngineHandler(this);
        boolean mRegisteredTimeZoneReceiver = false;
        Paint mBackgroundPaint;
        Paint mTextPaint;
        Paint mline;
        Paint mSos;
        boolean mAmbient;
        Time mTime;
        final BroadcastReceiver mTimeZoneReceiver = new BroadcastReceiver() {
            @Override
            public void onReceive(Context context, Intent intent) {
                mTime.clear(intent.getStringExtra("time-zone"));
                mTime.setToNow();
            }
        };
        int width,height;
        float mXOffset;
        float mYOffset;

        /**
         * Whether the display supports fewer bits for each color in ambient mode. When true, we
         * disable anti-aliasing in ambient mode.
         */
        boolean mLowBitAmbient;

        Timer timer;
        int timerCount = 10;
        boolean timerActive = false;
        GoogleApiClient mGoogleApiClient;

        SensorManager sensorManager;
        Sensor acSensor;
        Sensor hrSensor;
        Timer hrTimer;
        int hrPeriod = 5;
        /*
        private View myLayout;
        private int specW, specH;
        private final Point displaySize = new Point();
        */
        /*if(mTapCount%2==0){
                            sensorManager.unregisterListener(this,hrSensor);
                            sensorManager.registerListener(this, acSensor, SensorManager.SENSOR_DELAY_NORMAL);
                        }else{
                            sensorManager.unregisterListener(this,acSensor);
                            sensorManager.registerListener(this, hrSensor, SensorManager.SENSOR_DELAY_NORMAL);
                        }*/

        @Override
        public void onCreate(SurfaceHolder holder) {
            super.onCreate(holder);

            setWatchFaceStyle(new WatchFaceStyle.Builder(MyWatchFace.this)
                    .setCardPeekMode(WatchFaceStyle.PEEK_MODE_VARIABLE)
                    .setBackgroundVisibility(WatchFaceStyle.BACKGROUND_VISIBILITY_INTERRUPTIVE)
                    .setShowSystemUiTime(false)
                    .setAcceptsTapEvents(true)
                    .build());
            Resources resources = MyWatchFace.this.getResources();
            mBackgroundPaint = new Paint();
            mBackgroundPaint.setColor(resources.getColor(R.color.background));

            mTextPaint = new Paint();
            mTextPaint = createTextPaint(resources.getColor(R.color.digital_text));
            mline = new Paint();
            mline.setColor(getResources().getColor(R.color.red));
            mline.setStrokeWidth(2);
            mSos = new Paint();
            mSos = createTextPaint(resources.getColor(R.color.red));

            mTime = new Time();

            mGoogleApiClient = new GoogleApiClient.Builder(MyWatchFace.this)
                    .addApiIfAvailable(Wearable.API)
                    .addConnectionCallbacks(this)
                    .addOnConnectionFailedListener(this)
                    .build();
            mGoogleApiClient.connect();
            if(mGoogleApiClient.isConnected()){
                //sensorManager.registerListener(this, acSensor, SensorManager.SENSOR_DELAY_NORMAL);
                System.out.println("---------- isConnected ----------");
            }

            sensorManager = (SensorManager)getSystemService(Context.SENSOR_SERVICE);
            acSensor = sensorManager.getDefaultSensor(Sensor.TYPE_ACCELEROMETER);
            hrSensor = sensorManager.getDefaultSensor(Sensor.TYPE_HEART_RATE);
            hrTimer = new Timer();
            if(hrSensor != null) {
                hrTimer.schedule(new TimerTask() {
                    @Override
                    public void run() {
                        sensorManager.registerListener(Engine.this, hrSensor, SensorManager.SENSOR_DELAY_NORMAL);
                        System.out.println("Test screen off heart rate ");
                    }
                },10000,hrPeriod*60000);
            }
            if(acSensor != null){
                sensorManager.registerListener(Engine.this, acSensor, SensorManager.SENSOR_DELAY_NORMAL);
            }
            /*
            LayoutInflater inflater = (LayoutInflater)getSystemService(Context.LAYOUT_INFLATER_SERVICE);
            myLayout = inflater.inflate(R.layout.watchface, null);
            Display display = ((WindowManager) getSystemService(Context.WINDOW_SERVICE))
                    .getDefaultDisplay();
            display.getSize(displaySize);
            specW = View.MeasureSpec.makeMeasureSpec(displaySize.x,
                    View.MeasureSpec.EXACTLY);
            specH = View.MeasureSpec.makeMeasureSpec(displaySize.y,
                    View.MeasureSpec.EXACTLY);
            */
            // 量width與height
            WindowManager wm = (WindowManager) getSystemService(Context.WINDOW_SERVICE);
            Display display = wm.getDefaultDisplay();
            DisplayMetrics metrics = new DisplayMetrics();
            display.getMetrics(metrics);
            width = metrics.widthPixels;
            height = metrics.heightPixels;
            System.out.println("the screen width : "+width+" , height : "+height);
        }

        @Override
        public void onDestroy() {
            mUpdateTimeHandler.removeMessages(MSG_UPDATE_TIME);
            mGoogleApiClient.disconnect();
            hrTimer.cancel();
            hrTimer=null;
            sensorManager.unregisterListener(this, hrSensor);
            sensorManager.unregisterListener(this, acSensor);
            System.out.println("unregister all sensor listener");
            super.onDestroy();
        }

        private Paint createTextPaint(int textColor) {
            Paint paint = new Paint();
            paint.setColor(textColor);
            paint.setTypeface(NORMAL_TYPEFACE);
            paint.setAntiAlias(true);
            return paint;
        }

        @Override
        public void onVisibilityChanged(boolean visible) {
            super.onVisibilityChanged(visible);

            if (visible) {
                registerReceiver();

                // Update time zone in case it changed while we weren't visible.
                mTime.clear(TimeZone.getDefault().getID());
                mTime.setToNow();
            } else {
                unregisterReceiver();
            }

            // Whether the timer should be running depends on whether we're visible (as well as
            // whether we're in ambient mode), so we may need to start or stop the timer.
            updateTimer();
        }

        private void registerReceiver() {
            if (mRegisteredTimeZoneReceiver) {
                return;
            }
            mRegisteredTimeZoneReceiver = true;
            IntentFilter filter = new IntentFilter(Intent.ACTION_TIMEZONE_CHANGED);
            MyWatchFace.this.registerReceiver(mTimeZoneReceiver, filter);
        }

        private void unregisterReceiver() {
            if (!mRegisteredTimeZoneReceiver) {
                return;
            }
            mRegisteredTimeZoneReceiver = false;
            MyWatchFace.this.unregisterReceiver(mTimeZoneReceiver);
        }

        @Override
        public void onApplyWindowInsets(WindowInsets insets) {
            super.onApplyWindowInsets(insets);

            // Load resources that have alternate values for round watches.
            Resources resources = MyWatchFace.this.getResources();
            boolean isRound = insets.isRound();
            float textSize = resources.getDimension(isRound
                    ? R.dimen.digital_text_size_round : R.dimen.digital_text_size);
            if(width<=300){
                System.out.println("---------- small screen ----------");
                mXOffset = resources.getDimension(isRound
                        ? R.dimen.digital_x_offset_round_small : R.dimen.digital_x_offset_small);
                mYOffset = resources.getDimension(R.dimen.digital_y_offset_small);
            }else {
                System.out.println("---------- medium screen ----------");
                mXOffset = resources.getDimension(isRound
                        ? R.dimen.digital_x_offset_round_medium : R.dimen.digital_x_offset_medium);
                mYOffset = resources.getDimension(R.dimen.digital_y_offset_medium);
            }
            mTextPaint.setTextSize(textSize);
            mSos.setTextSize(textSize);
        }

        @Override
        public void onPropertiesChanged(Bundle properties) {
            super.onPropertiesChanged(properties);
            mLowBitAmbient = properties.getBoolean(PROPERTY_LOW_BIT_AMBIENT, false);
        }

        @Override
        public void onTimeTick() {
            super.onTimeTick();
            invalidate();
        }

        @Override
        public void onAmbientModeChanged(boolean inAmbientMode) {
            super.onAmbientModeChanged(inAmbientMode);
            if (mAmbient != inAmbientMode) {
                mAmbient = inAmbientMode;
                if (mLowBitAmbient) {
                    mTextPaint.setAntiAlias(!inAmbientMode);
                }
                invalidate();
            }

            // Whether the timer should be running depends on whether we're visible (as well as
            // whether we're in ambient mode), so we may need to start or stop the timer.
            updateTimer();
        }

        /**
         * Captures tap event (and tap type) and toggles the background color if the user finishes
         * a tap.
         */
        @Override
        public void onTapCommand(int tapType, int x, int y, long eventTime) {
            Resources resources = MyWatchFace.this.getResources();
            switch (tapType) {
                /*
                case TAP_TYPE_TOUCH:
                    // The user has started touching the screen.
                    break;
                case TAP_TYPE_TOUCH_CANCEL:
                    // The user has started a different gesture or otherwise cancelled the tap.
                    break;
                */
                case TAP_TYPE_TAP:
                    // The user has completed the tap gesture.
                    if(!timerActive) {
                        if (y >= height * 0.6) {
                            System.out.println("按下求救按鈕 , 進入倒數模式");
                            detect();
                        }
                    }else{
                        timer.cancel();
                        timerCount = 10;
                        timerActive = false;
                        System.out.println("按下取消求救 , 取消倒數");
                    }
                    break;
            }
            invalidate();
        }

        @Override
        public void onDraw(Canvas canvas, Rect bounds) {
            // Draw the background.
            if (isInAmbientMode()) {
                canvas.drawColor(Color.BLACK);
            } else {
                canvas.drawRect(0, 0, bounds.width(), bounds.height(), mBackgroundPaint);
            }

            // Draw H:MM in ambient mode or H:MM:SS in interactive mode.
            mTime.setToNow();

            String text;
            if(!timerActive) {
                text = mAmbient
                        ? String.format("%d:%02d", mTime.hour, mTime.minute)
                        : String.format("%d:%02d:%02d", mTime.hour, mTime.minute, mTime.second);
                canvas.drawLine(0,(float)(bounds.height()*0.6),bounds.width(),(float)(bounds.height()*0.6),mline);
                canvas.drawText("S",(float)(bounds.width()*0.2),(float)(bounds.height()*0.85),mSos);
                canvas.drawText("O",(float)(bounds.width()*0.4),(float)(bounds.height()*0.85),mSos);
                canvas.drawText("S",(float)(bounds.width()*0.6),(float)(bounds.height()*0.85),mSos);
            }else{
                text = timerCount+"秒求救";
                canvas.drawText("取消",(float)(bounds.width()*0.2),(float)(bounds.height()*0.85),mSos);
            }
            //canvas.drawColor(Color.BLACK);
            canvas.drawText(text, mXOffset, mYOffset, mTextPaint);


            //myLayout.measure(specW, specH);
            //myLayout.layout(0, 0, myLayout.getMeasuredWidth(), myLayout.getMeasuredHeight());
            //myLayout.draw(canvas);
        }

        /**
         * Starts the {@link #mUpdateTimeHandler} timer if it should be running and isn't currently
         * or stops it if it shouldn't be running but currently is.
         */
        private void updateTimer() {
            mUpdateTimeHandler.removeMessages(MSG_UPDATE_TIME);
            if (shouldTimerBeRunning()) {
                mUpdateTimeHandler.sendEmptyMessage(MSG_UPDATE_TIME);
            }
        }

        /**
         * Returns whether the {@link #mUpdateTimeHandler} timer should be running. The timer should
         * only run when we're visible and in interactive mode.
         */
        private boolean shouldTimerBeRunning() {
            return isVisible() && !isInAmbientMode();
        }

        /**
         * Handle updating the time periodically in interactive mode.
         */
        private void handleUpdateTimeMessage() {
            invalidate();
            if (shouldTimerBeRunning()) {
                long timeMs = System.currentTimeMillis();
                long delayMs = INTERACTIVE_UPDATE_RATE_MS
                        - (timeMs % INTERACTIVE_UPDATE_RATE_MS);
                mUpdateTimeHandler.sendEmptyMessageDelayed(MSG_UPDATE_TIME, delayMs);
            }
        }

        @Override
        public void onConnected(@Nullable Bundle bundle) {
            Toast.makeText(MyWatchFace.this, "已連線", Toast.LENGTH_LONG).show();
        }

        @Override
        public void onConnectionSuspended(int i) {
//            Toast.makeText(MyWatchFace.this,"連線暫停",Toast.LENGTH_LONG).show();
            new Timer().schedule(new TimerTask() {
                @Override
                public void run() {
                    if(!mGoogleApiClient.isConnected()){
                        mGoogleApiClient.connect();
                    }
                }
            },180000);
        }

        @Override
        public void onConnectionFailed(@NonNull ConnectionResult connectionResult) {
            Toast.makeText(MyWatchFace.this, "連線失敗,請檢查Google Play Service", Toast.LENGTH_LONG).show();
            new Timer().schedule(new TimerTask() {
                @Override
                public void run() {
                    if(!mGoogleApiClient.isConnected()){
                        mGoogleApiClient.connect();
                    }
                }
            },180000);
        }

        @Override
        public void onDataChanged(DataEventBuffer dataEventBuffer) {

        }

        int hrCount = 0;
        double tempHr = 0;
        @Override
        public void onSensorChanged(SensorEvent event) {
            Sensor sensor = event.sensor;
            if(sensor.getType() == Sensor.TYPE_ACCELEROMETER){
                //sendData("ac_value", event.values[0] + "," + event.values[1] + "," + event.values[2]);
                //System.out.println("ac value : "+event.values[0] + "," + event.values[1] + "," + event.values[2]);
                if(Math.abs(event.values[0])+Math.abs(event.values[1])+Math.abs(event.values[2]) >= 32){
                    System.out.println("ac value : "+event.values[0] + "," + event.values[1] + "," + event.values[2]);
                    if(!timerActive){
                        detect();
                    }
                }
            }else if(sensor.getType() == Sensor.TYPE_HEART_RATE){
                tempHr += event.values[0];
                System.out.println((hrCount+1) + " / 3 heart rate : " + event.values[0]);
                if(hrCount < 2){
                    hrCount++;
                }else if(hrCount == 2){
                    int recordHr = (int)tempHr/3;
                    sendHrData(recordHr,System.currentTimeMillis());
                    System.out.println("record heart rate : " + recordHr);
                    System.out.println(new SimpleDateFormat("yyyy年MM月dd日 HH:mm").format(new Date(System.currentTimeMillis())));
                    sensorManager.unregisterListener(this,hrSensor);
                    tempHr = 0;
                    hrCount = 0;
                }

            }
        }

        @Override
        public void onAccuracyChanged(Sensor sensor, int accuracy) {

        }

        public void sendHrData(double value, long time){
            PutDataMapRequest putDataMapRequest = PutDataMapRequest.create("/hrData");
            putDataMapRequest.getDataMap().putDouble("recordHr", value);
            putDataMapRequest.getDataMap().putLong("time", time);
            PutDataRequest putDataRequest = putDataMapRequest.asPutDataRequest();
            Wearable.DataApi.putDataItem(mGoogleApiClient, putDataRequest);
        }

        public void detect(){
            System.out.println("---------- 進入倒數模式 ----------");
            timerActive = true;
            final Vibrator mVibrator = (Vibrator) getApplication().getSystemService(Service.VIBRATOR_SERVICE);
            timer = new Timer();
            timer.schedule(new TimerTask() {
                @Override
                public void run() {
                    if(timerCount-- != 0) {
                        mVibrator.vibrate(500);
                    }else{
                        System.out.println("倒數未被取消 , 立即求救");
                        sendSosSignal();
                    }
                }
            },0,1000);
        }

        int sosCount = 0;
        public void sendSosSignal(){
            PutDataMapRequest putDataMapRequest = PutDataMapRequest.create("/SosSignal");
            putDataMapRequest.getDataMap().putInt("SOS", sosCount++);
            PutDataRequest putDataRequest = putDataMapRequest.asPutDataRequest();
            Wearable.DataApi.putDataItem(mGoogleApiClient, putDataRequest);
            timerActive = false;
            timer.cancel();
            timerCount = 10;
            System.out.println("已發送求救訊號");
        }

    }
}
