package com.example.user.fich;

import android.graphics.Bitmap;

/**
 * Created by huang on 2016/8/9.
 */
public class Contact {
    private int id;
    private Bitmap image;
    private String name;
    private String phone;

    public Contact(int id, Bitmap image, String name, String phone){
        this.id = id;
        this.image = image;
        this.name = name;
        this.phone = phone;
    }

    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public Bitmap getImage() {
        return image;
    }

    public void setImage(Bitmap image) {
        this.image = image;
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public String getPhone() {
        return phone;
    }

    public void setPhone(String phone) {
        this.phone = phone;
    }
}
