package com.example.user.fich;

/**
 * Created by huang on 2016/7/17.
 */
public enum Action {
    CREATE_USER("create", 0),
    DELETE_USER("delete", 1),
    UPDATE_USER("update", 2),
    SELECT_USER("select", 3),
    USER_LOGIN("login", 4),
    SENSOR_SAVE("create", 5),
    SENSOR_SELECT("select", 6),
    LOC_SAVE("create_loc", 7),
    LOC_SELECT("select_loc", 8),
    HEART_SAVE("create_heart", 9),
    HEART_SELECT("select_heart", 10),
    CHECK_MATCH("check", 11),
    AUTH("auth", 12);

    Action(String action, int value){
        this.action_string = action;
        this.value = value;
    }

    private String action_string;
    private int value;

    public int getValue(){
        return this.value;
    }

    @Override
    public String toString() {
        return this.action_string;
    }
}
