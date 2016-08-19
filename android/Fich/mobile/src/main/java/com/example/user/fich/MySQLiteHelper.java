package com.example.user.fich;

import android.content.ContentValues;
import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;

import java.util.ArrayList;

/**
 * Created by Ga on 2016/8/16.
 */
public class MySQLiteHelper extends SQLiteOpenHelper{
    private static final String DB_NAME = "FichSQLite";
    private static final int DB_VERSION = 2;
    private static final String TABLE_NAME = "LOCATION_RECORD";
//    private static final String COL_ID = "id";
    private static final String COL_LNG = "longitude";
    private static final String COL_LAT = "latitude";
    private static final String COL_TIME = "time";
    private static final String TABLE_CREATE = "CREATE TABLE " + TABLE_NAME + " ( " +
//            COL_ID + " INTEGER PRIMARY KEY , " +
            COL_LNG + " DOUBLE NOT NULL, " +
            COL_LAT + " DOUBLE NOT NULL, " +
            COL_TIME + " LONG NOT NULL ); ";

    public MySQLiteHelper(Context context) {
        super(context, DB_NAME, null, DB_VERSION);
    }

    @Override
    public void onCreate(SQLiteDatabase db) {
        db.execSQL(TABLE_CREATE);
    }

    @Override
    public void onUpgrade(SQLiteDatabase db, int oldVersion, int newVersion) {
        db.execSQL("DROP TABLE IF EXISTS " + TABLE_NAME);
        onCreate(db);
    }

    public long insert(MyLocation loc){
        SQLiteDatabase db = getWritableDatabase();
        ContentValues values = new ContentValues();
        values.put(COL_LNG, loc.getLongitude());
        values.put(COL_LAT, loc.getLatitude());
        values.put(COL_TIME, loc.getUnixTime());
        return db.insert(TABLE_NAME, null, values);
    }

    public ArrayList<MyLocation> getList(){
        SQLiteDatabase db = getReadableDatabase();
        String[] columns = {
                COL_LNG, COL_LAT,COL_TIME
        };
        Cursor cursor = db.query(TABLE_NAME, columns, null, null, null, null, null);
        ArrayList<MyLocation> locList = new ArrayList<>();
        while(cursor.moveToNext()){
            double longitude = cursor.getDouble(0);
            double latitude = cursor.getDouble(1);
            long time = cursor.getLong(2);
            MyLocation loc = new MyLocation(longitude,latitude,time);
            locList.add(loc);
        }
        cursor.close();
        return locList;
    }

    public boolean deleteAll() {
        SQLiteDatabase db = getReadableDatabase();
        return db.delete(TABLE_NAME, null, null)>0;
    }
}
