package layout;

import android.app.Activity;
import android.content.Context;
import android.location.Criteria;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.net.Uri;
import android.os.AsyncTask;
import android.os.Bundle;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Toast;

import com.example.user.fich.DataService;
import com.example.user.fich.MyLocation;
import com.example.user.fich.MySQLiteHelper;
import com.example.user.fich.R;
import com.google.android.gms.maps.CameraUpdateFactory;
import com.google.android.gms.maps.GoogleMap;
import com.google.android.gms.maps.MapView;
import com.google.android.gms.maps.MapsInitializer;
import com.google.android.gms.maps.OnMapReadyCallback;
import com.google.android.gms.maps.model.LatLng;
import com.google.android.gms.maps.model.Marker;
import com.google.android.gms.maps.model.MarkerOptions;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.List;

/**
 * A simple {@link Fragment} subclass.
 * Activities that contain this fragment must implement the
 * {@link MapFragment.OnFragmentInteractionListener} interface
 * to handle interaction events.
 * Use the {@link MapFragment#newInstance} factory method to
 * create an instance of this fragment.
 */
public class MapFragment extends Fragment implements OnMapReadyCallback {
    // TODO: Rename parameter arguments, choose names that match
    // the fragment initialization parameters, e.g. ARG_ITEM_NUMBER
    private static final String ARG_PARAM1 = "param1";
    private static final String ARG_PARAM2 = "param2";

    private GoogleMap mMap;
    private DataService dataService;
    private ArrayList<MyLocation> locList;

    // TODO: Rename and change types of parameters
    private String mParam1;
    private String mParam2;
    private MapView mapView;
    private LocationManager lm;
    MyLocListener myLocListener;

    private MySQLiteHelper helper;

    //private OnFragmentInteractionListener mListener;

    MapView mMapView;

    public MapFragment() {
        // Required empty public constructor
    }



    /**
     * Use this factory method to create a new instance of
     * this fragment using the provided parameters.
     *
     * @param param1 Parameter 1.
     * @param param2 Parameter 2.
     * @return A new instance of fragment MapFragment.
     */
    // TODO: Rename and change types and number of parameters
    public static MapFragment newInstance(String param1, String param2) {
        Log.e("~~DEBUG~~", "MAP ON");
        MapFragment fragment = new MapFragment();
        Bundle args = new Bundle();
        //args.putString(ARG_PARAM1, param1);
        //args.putString(ARG_PARAM2, param2);
        fragment.setArguments(args);
        return fragment;
    }

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        Log.e("~~DEBUG~~", "map onCreate");
        if (helper == null) {
            helper = new MySQLiteHelper(getActivity()); // ***待測試***
        }

        /*
        if (getArguments() != null) {
            mParam1 = getArguments().getString(ARG_PARAM1);
            mParam2 = getArguments().getString(ARG_PARAM2);
        }
        */

    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        Log.e("~~DEBUG~~", "map onCreateView");
        // 抓資料 待測試
//        locList = helper.getList();
//        for(MyLocation m:locList){
//            System.out.println(m.toString());
//        }
        View rootView = inflater.inflate(R.layout.fragment_map, container, false);
        mapView = (MapView) rootView.findViewById(R.id.map);

        mapView.onCreate(savedInstanceState);
        mapView.onResume();
        MapsInitializer.initialize(getActivity());
        mapView.getMapAsync(this);

        return rootView;
    }

    // TODO: Rename method, update argument and hook method into UI event
    public void onButtonPressed(Uri uri) {
        /*
        if (mListener != null) {
            mListener.onFragmentInteraction(uri);
        }
        */
    }

    @Override
    public void onResume(){
        super.onResume();
        Log.e("~~DEBUG~~", "map onResume");
        mapView.onResume();
    }

    @Override
    public void onPause(){
        super.onPause();
        Log.e("~~DEBUG~~", "map onPause");
        mapView.onPause();
        try {
            if(lm != null)
                lm.removeUpdates(myLocListener);
        }catch (SecurityException e){

        }
    }

    @Override
    public void onDestroy() {
        super.onDestroy();
        Log.e("~~DEBUG~~", "map onDestroy");
        mapView.onDestroy();
        if (helper != null) {
            helper.close();
        }
    }

    @Override
    public void onLowMemory() {
        super.onLowMemory();
        Log.e("~~DEBUG~~", "map onLowMemory");
        mapView.onLowMemory();
    }

    @Override
    public void onAttach(Context context) {
        super.onAttach(context);
        Log.e("~~DEBUG~~", "map onAttach");
        /*
        if (context instanceof OnFragmentInteractionListener) {
            mListener = (OnFragmentInteractionListener) context;
        } else {
            throw new RuntimeException(context.toString()
                    + " must implement OnFragmentInteractionListener");
        }
        */
    }

    @Override
    public void onDetach() {
        super.onDetach();
        Log.e("~~DEBUG~~", "map onDetach");
        //mListener = null;
    }

    @Override
    public void onMapReady(GoogleMap googleMap) {
        Log.e("~~DEBUG~~", "map onMapReady");
        mMap = googleMap;
        try {
            mMap.setMyLocationEnabled(true);
        }catch (SecurityException e){

        }
        mMap.moveCamera(CameraUpdateFactory.newLatLng(new LatLng(23.8246732,121.0472636)));
        mMap.moveCamera(CameraUpdateFactory.zoomTo(8));
        /*LatLng latlng =null;
        for(MyLocation loc:locList){

            latlng = new LatLng(loc.getLatitude(),loc.getLongitude());
            mMap.addMarker(new MarkerOptions().position(latlng).title(loc.getDateTime()));
        }
        if(latlng == null){
            mMap.moveCamera(CameraUpdateFactory.newLatLng(new LatLng(23.8246732,121.0472636)));
            mMap.moveCamera(CameraUpdateFactory.zoomTo(8));
        }else{
            mMap.moveCamera(CameraUpdateFactory.newLatLng(latlng));
            mMap.moveCamera(CameraUpdateFactory.zoomTo(15));
        }*/
        myLocListener = new MyLocListener();
        lm = (LocationManager) getActivity().getSystemService(Activity.LOCATION_SERVICE);
        String bestProvider = lm.getBestProvider(new Criteria(), true); // 取得最佳的定位提供者
        if (bestProvider.equals("gps") || bestProvider.equals("network")) { // 有開定位
            System.out.println("MyLocation : On , the locating provider is " + bestProvider);
            try {
                lm.requestLocationUpdates(bestProvider, 180000, 0, myLocListener);
            }catch (SecurityException e){

            }
            Toast.makeText(getActivity(), "定位中", Toast.LENGTH_LONG).show();
        } else { // 沒開定位,傳送通知提醒
            Toast.makeText(getActivity(), "定位服務未開啟 , 無法使用鄰近醫療單位功能", Toast.LENGTH_LONG).show();
        }
    }

    /**
     * This interface must be implemented by activities that contain this
     * fragment to allow an interaction in this fragment to be communicated
     * to the activity and potentially other fragments contained in that
     * activity.
     * <p/>
     * See the Android Training lesson <a href=
     * "http://developer.android.com/training/basics/fragments/communicating.html"
     * >Communicating with Other Fragments</a> for more information.
     */
    public interface OnFragmentInteractionListener {
        // TODO: Update argument type and name
        void onFragmentInteraction(Uri uri);
    }

    public class MyLocListener implements LocationListener {

        @Override
        public void onLocationChanged(Location location) {
            String str="尋找附近醫療資源 - 定位中\n";
            str+= "定位提供者:"+location.getProvider()+"\n";
            str+= String.format("經度:%.6f\n緯度:%.6f\n時間:%s\n",
                    location.getLongitude(),	// 經度
                    location.getLatitude(),		// 緯度
                    new SimpleDateFormat("yyyy年MM月dd日 HH:mm").format(new Date(location.getTime()))); // 時間
            str+= "--------------------";
            System.out.println(str);
            new PlacesTask().execute(searchReq(location.getLatitude(), location.getLongitude()));
            mMap.moveCamera(CameraUpdateFactory.newLatLngZoom(new LatLng(location.getLatitude(), location.getLongitude()), 13));

        }

        @Override
        public void onStatusChanged(String provider, int status, Bundle extras) {

        }

        @Override
        public void onProviderEnabled(String provider) {

        }

        @Override
        public void onProviderDisabled(String provider) {

        }
    }

    public String searchReq(double mLatitude, double mLongitude) {

        //use your current location here
        //double mLatitude = 24.9694856;
        //double mLongitude = 121.1903276;

        StringBuilder sb = new StringBuilder("https://maps.googleapis.com/maps/api/place/nearbysearch/json?");
        sb.append("location=" + mLatitude + "," + mLongitude);
        sb.append("&radius=5000");
        sb.append("&types=" + "hospital");
        sb.append("&sensor=true");
        sb.append("&key=AIzaSyBke1_igYVgoZ77uxBo0zKpI8CSGKy8-KE");
        sb.append("&language=zh-TW");

        Log.d("Map", "api: " + sb.toString());

        return sb.toString();
    }

    private class PlacesTask extends AsyncTask<String, Integer, String> {

        String data = null;

        // Invoked by execute() method of this object
        @Override
        protected String doInBackground(String... url) {
            try {
                data = downloadUrl(url[0]);
            } catch (Exception e) {
                Log.d("Background Task", e.toString());
            }
            return data;
        }

        // Executed after the complete execution of doInBackground() method
        @Override
        protected void onPostExecute(String result) {
            ParserTask parserTask = new ParserTask();

            // Start parsing the Google places in JSON format
            // Invokes the "doInBackground()" method of the class ParserTask
            parserTask.execute(result);
        }

        private String downloadUrl(String strUrl) throws IOException {
            String data = "";
            InputStream iStream = null;
            HttpURLConnection urlConnection = null;
            try {
                URL url = new URL(strUrl);

                // Creating an http connection to communicate with url
                urlConnection = (HttpURLConnection) url.openConnection();

                // Connecting to url
                urlConnection.connect();

                // Reading data from url
                iStream = urlConnection.getInputStream();

                BufferedReader br = new BufferedReader(new InputStreamReader(iStream));

                StringBuffer sb = new StringBuffer();

                String line = "";
                while ((line = br.readLine()) != null) {
                    sb.append(line);
                }

                data = sb.toString();

                br.close();

            } catch (Exception e) {
//                Log.d("Exception while downloading url", e.toString());
            } finally {
                iStream.close();
                urlConnection.disconnect();
            }
            return data;
        }

        private class ParserTask extends AsyncTask<String, Integer, List<HashMap<String, String>>> {

            JSONObject jObject;

            // Invoked by execute() method of this object
            @Override
            protected List<HashMap<String, String>> doInBackground(String... jsonData) {

                List<HashMap<String, String>> places = null;
                Place_JSON placeJson = new Place_JSON();

                try {
                    jObject = new JSONObject(jsonData[0]);

                    places = placeJson.parse(jObject);

                } catch (Exception e) {
                    Log.d("Exception", e.toString());
                }
                return places;
            }

            // Executed after the complete execution of doInBackground() method
            @Override
            protected void onPostExecute(List<HashMap<String, String>> list) {

                Log.d("Map", "list size: " + list.size());
                // Clears all the existing markers;
                mMap.clear();

                for (int i = 0; i < list.size(); i++) {

                    // Creating a marker
                    MarkerOptions markerOptions = new MarkerOptions();

                    // Getting a place from the places list
                    HashMap<String, String> hmPlace = list.get(i);


                    // Getting latitude of the place
                    double lat = Double.parseDouble(hmPlace.get("lat"));

                    // Getting longitude of the place
                    double lng = Double.parseDouble(hmPlace.get("lng"));

                    // Getting name
                    String name = hmPlace.get("place_name");

                    Log.d("Map", "place: " + name);

                    // Getting vicinity
                    String vicinity = hmPlace.get("vicinity");

                    LatLng latLng = new LatLng(lat, lng);

                    // Setting the position for the marker
                    markerOptions.position(latLng);

                    markerOptions.title(name + " : " + vicinity);

                    // Placing a marker on the touched position
                    Marker m = mMap.addMarker(markerOptions);

                }

            }

            public class Place_JSON {

                /**
                 * Receives a JSONObject and returns a list
                 */
                public List<HashMap<String, String>> parse(JSONObject jObject) {

                    JSONArray jPlaces = null;
                    try {
                        /** Retrieves all the elements in the 'places' array */
                        jPlaces = jObject.getJSONArray("results");
                    } catch (JSONException e) {
                        e.printStackTrace();
                    }
                    /** Invoking getPlaces with the array of json object
                     * where each json object represent a place
                     */
                    return getPlaces(jPlaces);
                }

                private List<HashMap<String, String>> getPlaces(JSONArray jPlaces) {
                    int placesCount = jPlaces.length();
                    List<HashMap<String, String>> placesList = new ArrayList<HashMap<String, String>>();
                    HashMap<String, String> place = null;

                    /** Taking each place, parses and adds to list object */
                    for (int i = 0; i < placesCount; i++) {
                        try {
                            /** Call getPlace with place JSON object to parse the place */
                            place = getPlace((JSONObject) jPlaces.get(i));
                            placesList.add(place);
                        } catch (JSONException e) {
                            e.printStackTrace();
                        }
                    }
                    return placesList;
                }

                /**
                 * Parsing the Place JSON object
                 */
                private HashMap<String, String> getPlace(JSONObject jPlace) {

                    HashMap<String, String> place = new HashMap<String, String>();
                    String placeName = "-NA-";
                    String vicinity = "-NA-";
                    String latitude = "";
                    String longitude = "";
                    String reference = "";

                    try {
                        // Extracting Place name, if available
                        if (!jPlace.isNull("name")) {
                            placeName = jPlace.getString("name");
                        }

                        // Extracting Place Vicinity, if available
                        if (!jPlace.isNull("vicinity")) {
                            vicinity = jPlace.getString("vicinity");
                        }

                        latitude = jPlace.getJSONObject("geometry").getJSONObject("location").getString("lat");
                        longitude = jPlace.getJSONObject("geometry").getJSONObject("location").getString("lng");
                        reference = jPlace.getString("reference");

                        place.put("place_name", placeName);
                        place.put("vicinity", vicinity);
                        place.put("lat", latitude);
                        place.put("lng", longitude);
                        place.put("reference", reference);

                    } catch (JSONException e) {
                        e.printStackTrace();
                    }
                    return place;
                }
            }

        }
    }
}
