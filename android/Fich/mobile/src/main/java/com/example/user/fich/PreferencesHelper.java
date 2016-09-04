package com.example.user.fich;

import android.content.Context;
import android.content.SharedPreferences;

import java.util.ArrayList;
import java.util.HashSet;
import java.util.Iterator;
import java.util.Set;

/**
 * Created by huang on 2016/8/17.
 */
public class PreferencesHelper {

    private static final String file = "fich";

    SharedPreferences pref;
    SharedPreferences.Editor prefEdit;

    public PreferencesHelper(Context context){
        pref = context.getSharedPreferences(file, Context.MODE_PRIVATE);
        prefEdit = pref.edit();
    }

    public void storeData(String key, String value){
        prefEdit.putString(key, value);
        prefEdit.apply();
    }

    public void storeData(String key, int value){
        prefEdit.putInt(key, value);
        prefEdit.apply();
    }

    public void storeData(String key, float value){
        prefEdit.putFloat(key, value);
        prefEdit.apply();
    }

    public void storeData(String key, boolean value){
        prefEdit.putBoolean(key, value);
        prefEdit.apply();
    }

    public void storeData(String key, Set<String> value){
        prefEdit.putStringSet(key, value);
        prefEdit.apply();
    }

    public String getString(String key){
        return pref.getString(key, null);
    }

    public int getInt(String key){
        return pref.getInt(key, 0);
    }

    public Float getFloat(String key){
        return pref.getFloat(key, 0);
    }

    public Boolean getBoolean(String key){
        return pref.getBoolean(key, false);
    }

    public Set<String> getStringSet(String key){
        return pref.getStringSet(key, null);
    }

    public ArrayList<Contact> getContactList(){
        HashSet<String> hs = (HashSet<String>)getStringSet("contactList");
        if(hs != null) {
            ArrayList<Contact> al = new ArrayList<>();
            Iterator<String> iter = hs.iterator();
            while (iter.hasNext()) {
                String[] c = iter.next().split(",");
                al.add(new Contact(c[0], c[1]));
            }
            return al;
        }
        return new ArrayList<>();
    }
}
