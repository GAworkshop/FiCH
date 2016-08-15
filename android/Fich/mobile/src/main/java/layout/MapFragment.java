package layout;

import android.content.ComponentName;
import android.content.Context;
import android.content.ServiceConnection;
import android.net.Uri;
import android.os.Bundle;
import android.os.IBinder;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;

import com.example.user.fich.DataService;
import com.example.user.fich.R;
import com.google.android.gms.maps.CameraUpdateFactory;
import com.google.android.gms.maps.GoogleMap;
import com.google.android.gms.maps.MapView;
import com.google.android.gms.maps.MapsInitializer;
import com.google.android.gms.maps.OnMapReadyCallback;
import com.google.android.gms.maps.model.CameraPosition;
import com.google.android.gms.maps.model.LatLng;
import com.google.android.gms.maps.model.MarkerOptions;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;

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
    private ArrayList<com.example.user.fich.Location> locList;

    // TODO: Rename and change types of parameters
    private String mParam1;
    private String mParam2;

    //private OnFragmentInteractionListener mListener;

    MapView mMapView;

    public MapFragment() {
        // Required empty public constructor
    }

    private ServiceConnection dataServiceCon = new ServiceConnection() {
        @Override
        public void onServiceConnected(ComponentName name, IBinder binder) {

            locList = dataService.getLocList();

            // Obtain the SupportMapFragment and get notified when the map is ready to be used.

        }
        @Override
        public void onServiceDisconnected(ComponentName name) {
            dataService = null;
        }
    };

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
        if (getArguments() != null) {
            mParam1 = getArguments().getString(ARG_PARAM1);
            mParam2 = getArguments().getString(ARG_PARAM2);
        }

    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        // Inflate the layout for this fragment
        View view = inflater.inflate(R.layout.fragment_map, container, false);

        mMapView = (MapView) view.findViewById(R.id.map);
        //mMapView.onCreate(savedInstanceState);
        //mMapView.onResume();
        try {
            MapsInitializer.initialize(getActivity().getApplicationContext());
        } catch (Exception e) {
            e.printStackTrace();
        }

        mMapView.getMapAsync(new OnMapReadyCallback() {
            @Override
            public void onMapReady(GoogleMap map) {
                mMap = mMap;

                // For showing a move to my location button
                try {
                    mMap.setMyLocationEnabled(true);
                }catch (SecurityException e){

                }
                // For dropping a marker at a point on the Map
                LatLng sydney = new LatLng(-34, 151);
                mMap.addMarker(new MarkerOptions().position(sydney).title("Marker Title").snippet("Marker Description"));

                // For zooming automatically to the location of the marker
                CameraPosition cameraPosition = new CameraPosition.Builder().target(sydney).zoom(12).build();
                mMap.animateCamera(CameraUpdateFactory.newCameraPosition(cameraPosition));


                LatLng latlng =null;
                for(com.example.user.fich.Location loc:locList){
                    latlng = new LatLng(loc.getLatitude(),loc.getLongitude());
                    mMap.addMarker(new MarkerOptions().position(latlng).title(new SimpleDateFormat("yyyy年MM月dd日 HH:mm").format(new Date(loc.getTime()))));
                }
                if(latlng == null){
                    mMap.moveCamera(CameraUpdateFactory.newLatLng(new LatLng(23.8246732,121.0472636)));
                    mMap.moveCamera(CameraUpdateFactory.zoomTo(8));
                }else{
                    mMap.moveCamera(CameraUpdateFactory.newLatLng(latlng));
                    mMap.moveCamera(CameraUpdateFactory.zoomTo(15));
                }
            }
        });

        return view;
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
    public void onAttach(Context context) {
        super.onAttach(context);
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
        //mListener = null;
    }

    @Override
    public void onMapReady(GoogleMap googleMap) {
        mMap = googleMap;

        LatLng latlng =null;
        for(com.example.user.fich.Location loc:locList){
            latlng = new LatLng(loc.getLatitude(),loc.getLongitude());
            mMap.addMarker(new MarkerOptions().position(latlng).title(new SimpleDateFormat("yyyy年MM月dd日 HH:mm").format(new Date(loc.getTime()))));
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
