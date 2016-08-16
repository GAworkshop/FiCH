package layout;

import android.content.Context;
import android.net.Uri;
import android.os.Bundle;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;

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
import com.google.android.gms.maps.model.MarkerOptions;

import java.util.ArrayList;

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
        // 抓資料 待測試
        locList = helper.getList();
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
        LatLng latlng =null;
        try {
            mMap.setMyLocationEnabled(true);
        }catch (SecurityException e){

        }
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
}
