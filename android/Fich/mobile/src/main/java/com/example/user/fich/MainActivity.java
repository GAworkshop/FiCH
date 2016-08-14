package com.example.user.fich;

import android.app.SearchManager;
import android.content.Context;
import android.content.Intent;
import android.hardware.SensorManager;
import android.os.Bundle;
import android.support.design.widget.NavigationView;
import android.support.v4.app.Fragment;
import android.support.v4.app.FragmentPagerAdapter;
import android.support.v4.view.ViewPager;
import android.support.v4.widget.DrawerLayout;
import android.support.v7.app.ActionBarDrawerToggle;
import android.support.v7.app.AppCompatActivity;
import android.support.v7.widget.SearchView;
import android.support.v7.widget.Toolbar;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import layout.CardFragment;
import layout.ContactFragment;

public class MainActivity extends AppCompatActivity {

    private DrawerLayout mDrawerLayout;

    private NavigationView left_drawer;

    private ActionBarDrawerToggle mActionBarDrawerToggle;

    private Toolbar toolbar;

    private android.support.design.widget.TabLayout mTabs;

    private ViewPager mViewPager;

    private String[] tab_titles = {"資訊卡", "緊急聯絡人", "個人資訊", "鄰近醫療單位", "逃生指引"};
    private int[] tab_icons = {
            R.drawable.ic_card,
            R.drawable.ic_contact,
            R.drawable.ic_info,
            R.drawable.ic_nearby,
            R.drawable.ic_escape
    };
    //private int[] fragmants_list = {R.layout.fragment_contact, R.layout.fragment_card, R.layout.fragment_card, R.layout.fragment_card, R.layout.fragment_card};

    SensorManager mSensorMgr;
    TextView tx, ty, tz;
    Toast toast;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        //System.out.println("GGGGGGGGGGGGGG");
        /*
        DBRequest dbRequest = new DBRequest(Action.SENSOR_SELECT);
        dbRequest.setPair("newest","5");
        MemberRequest m = new MemberRequest(dbRequest);
        m.execute(new DataCallback() {
            @Override
            public void onFinish(JSONArray jsonArray) {
                try {
                    for (int i = 0; i < jsonArray.length(); i++) {
                        Log.v(i+"", jsonArray.get(i).toString());

                        for (int j = 0; j < jsonArray.getJSONArray(i).length(); j++) {
                            //Log.e("(" + i + "," + j + ")", "" + jsonArray.getJSONArray(i).get(j).toString());
                        }

                    }
                }catch (JSONException e){
                    e.printStackTrace();
                }

            }
        });
*/

        //startActivity(new Intent(MainActivity.this, LoginActivity.class));
        //startActivity(new Intent(MainActivity.this, SettingsActivity.class));

        tx = (TextView) findViewById(R.id.textView6);
        ty = (TextView) findViewById(R.id.textView7);
        tz = (TextView) findViewById(R.id.textView8);
        toast = Toast.makeText(this, " ", Toast.LENGTH_SHORT);

        initToolbar();
        initViewPager();
        setListeners();

/*
        mSensorMgr = (SensorManager) getSystemService(SENSOR_SERVICE);
        Sensor mSensor = mSensorMgr.getDefaultSensor(Sensor.TYPE_ACCELEROMETER);
        mSensorMgr.registerListener(new SensorEventListener() {
            @Override
            public void onSensorChanged(SensorEvent e) {
                float x = e.values[0];
                float y = e.values[1];
                float z = e.values[2];
                //toast.cancel();
                //toast.setText("x=" + x + ",y=" + y + ",z=" + z);
                //toast.show();
            }

            @Override
            public void onAccuracyChanged(Sensor sensor, int accuracy) {

            }
        }, mSensor, SensorManager.SENSOR_DELAY_GAME);*/




    }

    @Override
    public void onBackPressed() {
        if (this.mDrawerLayout.isDrawerOpen(left_drawer)) {
            this.mDrawerLayout.closeDrawer(left_drawer);
        } else {
            super.onBackPressed();
        }
    }

    @Override
    protected void onDestroy(){
        super.onDestroy();
        //toast.cancel();

    }

    private void initToolbar(){
        toolbar = (Toolbar) findViewById(R.id.toolbar);
        // Set an OnMenuItemClickListener to handle menu item clicks
        toolbar.setOnMenuItemClickListener(new Toolbar.OnMenuItemClickListener() {
            @Override
            public boolean onMenuItemClick(MenuItem item) {
                // Handle the menu item

                return true;
            }
        });
        // Inflate a menu to be displayed in the toolbar
        toolbar.inflateMenu(R.menu.menu_main);
        setSupportActionBar(toolbar);

        getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        getSupportActionBar().setHomeButtonEnabled(true);

        mDrawerLayout = (DrawerLayout) findViewById(R.id.drawer_layout);
        left_drawer = (NavigationView) findViewById(R.id.left_drawer);

        mActionBarDrawerToggle = new ActionBarDrawerToggle(this, mDrawerLayout, toolbar, R.string.open, R.string.close) {
            @Override
            public void onDrawerOpened(View drawerView) {
                super.onDrawerOpened(drawerView);
                System.out.println("drawer opened");
                //new DBconnect().execute();
            }
            @Override
            public void onDrawerClosed(View drawerView) {
                super.onDrawerClosed(drawerView);
                System.out.println("drawer closed");
            }
        };
        mActionBarDrawerToggle.syncState();
        mDrawerLayout.addDrawerListener(mActionBarDrawerToggle);
    }

    private void initViewPager(){
        mTabs = (android.support.design.widget.TabLayout) findViewById(R.id.tabs);

        mViewPager = (ViewPager) findViewById(R.id.viewpager);
        mViewPager.setAdapter(new SamplePagerAdapter(getSupportFragmentManager()));
        //mViewPager.addOnPageChangeListener(new TabLayout.TabLayoutOnPageChangeListener(mTabs));

        mTabs.setupWithViewPager(mViewPager);
        //mTabs.getTabAt(0).setIcon(R.drawable.ic_card);

        //mTabs.getTabAt(0).setCustomView();

        for (int i = 0; i < tab_icons.length; i++){



            View v = LayoutInflater.from(this).inflate(R.layout.tab_item, null);
            ((ImageView)v.findViewById(R.id.iv_tab_icon)).setImageResource(tab_icons[i]);
            ((TextView)v.findViewById(R.id.tv_tab_icon)).setText(tab_titles[i]);
            mTabs.getTabAt(i).setCustomView(v);



            //mTabs.getTabAt(i).setIcon(tab_icons[i]);
        }
/*
        mTabs.setOnTabSelectedListener(new TabLayout.OnTabSelectedListener() {
            @Override
            public void onTabSelected(TabLayout.Tab tab) {
                mViewPager.setCurrentItem(mTabs.getSelectedTabPosition());
            }

            @Override
            public void onTabUnselected(TabLayout.Tab tab) {

            }

            @Override
            public void onTabReselected(TabLayout.Tab tab) {

            }
        });

        mViewPager.addOnPageChangeListener(new ViewPager.OnPageChangeListener() {
            @Override
            public void onPageScrolled(int position, float positionOffset, int positionOffsetPixels) {

            }

            @Override
            public void onPageSelected(int position) {

            }

            @Override
            public void onPageScrollStateChanged(int state) {

            }
        });
*/
    }

    private void setListeners(){
        left_drawer.setNavigationItemSelectedListener(new NavigationView.OnNavigationItemSelectedListener() {
            @Override
            public boolean onNavigationItemSelected(MenuItem item) {
                Log.e("~~DEBUG~~", "" + item.getOrder());
                item.setChecked(true);
                mViewPager.setCurrentItem(item.getOrder());
                mDrawerLayout.closeDrawer(left_drawer);
                return false;
            }
        });
    }

    private class SamplePagerAdapter extends FragmentPagerAdapter {

        public SamplePagerAdapter(android.support.v4.app.FragmentManager fm){
            super(fm);
        }

        @Override
        public int getCount() {
            return tab_titles.length;
        }

        @Override
        public android.support.v4.app.Fragment getItem(int position){

            switch (position){
                case 0:
                    return CardFragment.newInstance("", "");
                case 1:
                    return ContactFragment.newInstance("","");
                case 2:
                case 3:
                case 4:
                default:
                    return new Fragment();
            }

            /*
            if(position == 0)
                return ContactFragment.newInstance("","");
            else
                return new Fragment();
            */
        }

        @Override
        public CharSequence getPageTitle(int position) {
            //return null;
            return tab_titles[position];
        }




    }


    public boolean onCreateOptionsMenu(Menu menu) {
        MenuInflater inflater = getMenuInflater();
        inflater.inflate(R.menu.menu_main, menu);

        MenuItem menuSearchItem = menu.findItem(R.id.my_search);
        // Get the SearchView and set the searchable configuration
        SearchManager searchManager = (SearchManager) getSystemService(Context.SEARCH_SERVICE);
//        SearchView searchView = (SearchView) menuSearchItem.getActionView();
        SearchView searchView = (SearchView) menuSearchItem.getActionView();
        // Assumes current activity is the searchable activity
        searchView.setSearchableInfo(searchManager.getSearchableInfo(getComponentName()));
        searchView.setIconifiedByDefault(true); // Do not iconify the widget; expand it by default
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        Log.e("~~DEBUG~~", "onOptionsItemSelected: " + item.getItemId());
        switch (item.getItemId()) {
            case R.id.action_about:
                // About option clicked.
                return true;
            case R.id.action_exit:
                // Exit option clicked.
                return true;
            case R.id.action_settings:
                // Settings option clicked.
                return true;
            default:
                return super.onOptionsItemSelected(item);
        }
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
    }

}
