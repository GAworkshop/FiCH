package com.example.user.fich;

/**
 * Created by huang on 2016/7/17.
 */
public enum UserAction {
    DATA_UPLOAD("upload"),
    CREATE_USER("create"),
    DELETE_USER("delete"),
    UPDATE_USER("update"),
    SELECT_USER("select"),
    USER_LOGIN("login");

    UserAction(String action){
        this.action_string = action;
    }

    private String action_string;

    @Override
    public String toString() {
        return this.action_string;
    }
}
