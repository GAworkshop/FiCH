package layout;

import android.content.Context;
import android.graphics.Color;
import android.net.Uri;
import android.os.Bundle;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;

import com.example.user.fich.Action;
import com.example.user.fich.ConnectRequest;
import com.example.user.fich.DBRequest;
import com.example.user.fich.DataCallback;
import com.example.user.fich.MyHeartRate;
import com.example.user.fich.MySQLiteHelper;
import com.example.user.fich.PreferencesHelper;
import com.example.user.fich.R;
import com.github.mikephil.charting.charts.LineChart;
import com.github.mikephil.charting.data.Entry;
import com.github.mikephil.charting.data.LineData;
import com.github.mikephil.charting.data.LineDataSet;
import com.github.mikephil.charting.interfaces.datasets.ILineDataSet;

import org.json.JSONArray;

import java.util.ArrayList;

/**
 * A simple {@link Fragment} subclass.
 * Activities that contain this fragment must implement the
 * {@link SelfInfoFragment.OnFragmentInteractionListener} interface
 * to handle interaction events.
 * Use the {@link SelfInfoFragment#newInstance} factory method to
 * create an instance of this fragment.
 */
public class SelfInfoFragment extends Fragment {
    // TODO: Rename parameter arguments, choose names that match
    // the fragment initialization parameters, e.g. ARG_ITEM_NUMBER
    private static final String ARG_PARAM1 = "param1";
    private static final String ARG_PARAM2 = "param2";

    MySQLiteHelper sqLiteHelper;
    PreferencesHelper prefHelper;

    // TODO: Rename and change types of parameters
    private String mParam1;
    private String mParam2;

    LineChart lineChart;
    float[] points = {66,70,68,72,81,67,65,71,75,70};

    //private OnFragmentInteractionListener mListener;

    public SelfInfoFragment() {
        // Required empty public constructor
    }

    /**
     * Use this factory method to create a new instance of
     * this fragment using the provided parameters.
     *
     * @param param1 Parameter 1.
     * @param param2 Parameter 2.
     * @return A new instance of fragment SelfInfoFragment.
     */
    // TODO: Rename and change types and number of parameters
    public static SelfInfoFragment newInstance(String param1, String param2) {
        SelfInfoFragment fragment = new SelfInfoFragment();
        Bundle args = new Bundle();
        args.putString(ARG_PARAM1, param1);
        args.putString(ARG_PARAM2, param2);
        fragment.setArguments(args);
        return fragment;
    }

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
//        if (getArguments() != null) {
//            mParam1 = getArguments().getString(ARG_PARAM1);
//            mParam2 = getArguments().getString(ARG_PARAM2);
//        }
        sqLiteHelper = new MySQLiteHelper(getActivity());
        prefHelper = new PreferencesHelper(getActivity());

        DBRequest dbRequest = new DBRequest(Action.HEART_SELECT);
        dbRequest.setPair("id", prefHelper.getInt(getResources().getString(R.string.UID))+"" );
        dbRequest.setPair("newest", 10+"");
        ConnectRequest m = new ConnectRequest(dbRequest);
        m.execute(new DataCallback() {
            @Override
            public void onFinish(JSONArray jsonArray) {
                try {
                    Log.e("~~~~~~~~~~~~", ">>>>>>>>>>>>>>><<<<<<<<<<<<<<<<<<<<<"+jsonArray.optBoolean(0));


                }catch (Exception e){

                }

            }
        });
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        // Inflate the layout for this fragment

        View view = inflater.inflate(R.layout.fragment_self_info, container, false);

        ((Button)view.findViewById(R.id.btn_left)).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                scrollLeft(v);
            }
        });

        view.findViewById(R.id.btn_right).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                scrollRight(v);
            }
        });

        //ArrayList<MyHeartRate> array = sqLiteHelper.getHRList();



        lineChart = (LineChart) view.findViewById(R.id.lineChart1);

        ArrayList<String> xAXES = new ArrayList<>();
        ArrayList<Entry> yAXESheart = new ArrayList<>();
        double x = 0 ;
        int numDataPoints = 10;
        for(int i=0;i<numDataPoints;i++){
            //float heart = (float) (Math.random()*10+80);
            float heart = points[i];
            x+=40;
            yAXESheart.add(new Entry(i,heart));
            xAXES.add(i, String.valueOf(x));
        }
        String[] xaxes = new String[xAXES.size()];
        for(int i=0; i<xAXES.size();i++){
            xaxes[i] = xAXES.get(i).toString();
        }

        ArrayList<ILineDataSet> lineDataSets = new ArrayList<>();


        LineDataSet lineDataSet2 = new LineDataSet(yAXESheart,"heart");
        lineDataSet2.setDrawCircles(true);
        lineDataSet2.setColor(Color.RED);

        lineDataSets.add(lineDataSet2);

        lineChart.setData(new LineData(lineDataSets));
        lineChart.enableScroll();

        lineChart.setVisibleXRangeMaximum(120);
/*
        ArrayList<MyHeartRate> hr_arlist = sqLiteHelper.getHRList();
        ArrayList<Entry> hr_list = new ArrayList<>();
        for(int i=0;i<hr_arlist.size();i++){
            hr_list.add(new Entry(i,hr_arlist.get(i).getHeartRate()));
        }
        System.out.println("heart rate list size : " + hr_arlist.size());
        LineDataSet dataSet = new LineDataSet(hr_list, "heart");
        dataSet.setDrawCircles(true);
        dataSet.setColor(Color.RED);
        lineChart.setData(new LineData(dataSet));
        lineChart.enableScroll();
        lineChart.setVisibleXRangeMaximum(120);
*/
        return view;
    }

    public void scrollRight(View view){
        lineChart.scrollBy(5, lineChart.getScrollY());
        //Toast.makeText(getActivity(), "left", Toast.LENGTH_SHORT).show();
    }

    public void scrollLeft(View view){
        lineChart.scrollBy(-5, lineChart.getScrollY());
        //Toast.makeText(getActivity(), "right", Toast.LENGTH_SHORT).show();
    }

    // TODO: Rename method, update argument and hook method into UI event
    public void onButtonPressed(Uri uri) {
//        if (mListener != null) {
//            mListener.onFragmentInteraction(uri);
//        }
    }

    @Override
    public void onAttach(Context context) {
        super.onAttach(context);
//        if (context instanceof OnFragmentInteractionListener) {
//            mListener = (OnFragmentInteractionListener) context;
//        } else {
//            throw new RuntimeException(context.toString()
//                    + " must implement OnFragmentInteractionListener");
//        }
    }

    @Override
    public void onDetach() {
        super.onDetach();
        //mListener = null;
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
