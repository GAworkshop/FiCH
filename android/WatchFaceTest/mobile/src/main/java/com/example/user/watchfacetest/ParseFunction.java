package com.example.user.watchfacetest;

import org.json.JSONArray;

/**
 * Created by huang on 2016/8/4.
 */
public interface ParseFunction{
    public abstract JSONArray parse(String result);
}