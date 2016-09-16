package layout;

import android.app.AlertDialog;
import android.content.Context;
import android.content.ContextWrapper;
import android.content.DialogInterface;
import android.net.Uri;
import android.os.Bundle;
import android.support.v4.app.Fragment;
import android.text.Layout;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ListView;
import android.widget.TextView;

import com.example.user.fich.Action;
import com.example.user.fich.ConnectRequest;
import com.example.user.fich.DBRequest;
import com.example.user.fich.DataCallback;
import com.example.user.fich.FamilyData;
import com.example.user.fich.MainActivity;
import com.example.user.fich.PreferencesHelper;
import com.example.user.fich.R;

import org.json.JSONArray;
import org.w3c.dom.Text;

import java.util.ArrayList;
import java.util.zip.Inflater;

/**
 * A simple {@link Fragment} subclass.
 * Activities that contain this fragment must implement the
 * {@link FamilyFragment.OnFragmentInteractionListener} interface
 * to handle interaction events.
 * Use the {@link FamilyFragment#newInstance} factory method to
 * create an instance of this fragment.
 */
public class FamilyFragment extends Fragment {

    PreferencesHelper prefHelper;
    LayoutInflater flater;
    ArrayList<FamilyData> familyDataList = new ArrayList<FamilyData>();
    ListView familyDataListView;
    FamilyDataAdapter familyDataAdapter;

    public FamilyFragment() {
        // Required empty public constructor
    }

    /**
     * Use this factory method to create a new instance of
     * this fragment using the provided parameters.
     *
     * @param param1 Parameter 1.
     * @param param2 Parameter 2.
     * @return A new instance of fragment FamilyFragment.
     */
    // TODO: Rename and change types and number of parameters
    public static FamilyFragment newInstance(String param1, String param2) {
        FamilyFragment fragment = new FamilyFragment();
        Bundle args = new Bundle();
//        args.putString(ARG_PARAM1, param1);
//        args.putString(ARG_PARAM2, param2);
        fragment.setArguments(args);
        return fragment;
    }

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        if (getArguments() != null) {

        }
        prefHelper = new PreferencesHelper(getActivity());
    }

    @Override
    public View onCreateView(LayoutInflater inflater, final ViewGroup container,
                             Bundle savedInstanceState) {
        flater = inflater;
        // Inflate the layout for this fragment
        final View view = inflater.inflate(R.layout.fragment_family, container, false);

        familyDataListView = (ListView) view.findViewById(R.id.family_data_listview);
        familyDataAdapter = new FamilyDataAdapter(getActivity());
        familyDataListView.setAdapter(familyDataAdapter);

        Button inviteBtn = (Button)view.findViewById(R.id.inviteBtn);
        inviteBtn.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                final View item = flater.inflate(R.layout.email_input_layout, null);
                new AlertDialog.Builder(getActivity())
                        .setTitle("請輸入家庭成員信箱")
                        .setView(item)
                        .setPositiveButton("確認", new DialogInterface.OnClickListener() {
                            @Override
                            public void onClick(DialogInterface dialog, int which) {
                                EditText email_input_et= (EditText)item.findViewById(R.id.email_input_et);
                                String targetEmail = email_input_et.getText().toString().trim();

                                DBRequest dbRequest = new DBRequest(Action.MATCH_REQUEST);
                                dbRequest.setPair("wear_id", prefHelper.getInt(getResources().getString(R.string.UID))+"");
                                dbRequest.setPair("family_email", targetEmail);
                                ConnectRequest m = new ConnectRequest(dbRequest);
                                m.execute(new DataCallback() {
                                    @Override
                                    public void onFinish(JSONArray jsonArray) {
                                        //System.out.println("Ga test : " + jsonArray.toString());
                                    }
                                });

                            }
                        })
                        .setNegativeButton("取消", new DialogInterface.OnClickListener() {
                            @Override
                            public void onClick(DialogInterface dialog, int which) {

                            }
                        })
                        .show();

            }
        });
        return view;
    }

    // TODO: Rename method, update argument and hook method into UI event

    @Override
    public void onAttach(Context context) {
        super.onAttach(context);
    }

    @Override
    public void onDetach() {
        super.onDetach();
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

    public void onResume(){
        super.onResume();
        DBRequest dbRequest = new DBRequest(Action.LOOK_FAMILY_DATA);
        dbRequest.setPair("wear_id", prefHelper.getInt(getResources().getString(R.string.UID))+"");
        ConnectRequest m = new ConnectRequest(dbRequest);
        m.execute(new DataCallback() {
            @Override
            public void onFinish(JSONArray jsonArray) {
                String dataString = jsonArray.toString();
                dataString = dataString.replace("\"", "").replace("[", "").replace("]", "");
                String [] dataArray = dataString.split("&");
                for(int i=0;i<dataArray.length;i++){
                    String [] data = dataArray[i].split(",");
                    familyDataList.add(new FamilyData(data[0],data[1],data[2],data[3]));
                    System.out.println("listview item : "+familyDataList.size());
                }
                familyDataAdapter.notifyDataSetChanged();
                System.out.println("listview item : "+familyDataList.size());
            }
        });
    }

    public void onPause(){
        super.onPause();
        familyDataList = new ArrayList<FamilyData>();
    }

    private class FamilyDataAdapter extends BaseAdapter{

        private LayoutInflater layoutInflater;
        public FamilyDataAdapter(Context context){
            layoutInflater = LayoutInflater.from(context);
        }

        @Override
        public int getCount() {
            return familyDataList.size();
        }

        @Override
        public Object getItem(int position) {
            return familyDataList.get(position);
        }

        @Override
        public long getItemId(int position) {
            return familyDataList.get(position).getId();
        }

        @Override
        public View getView(int position, View convertView, ViewGroup parent) {
            if (convertView == null) {
                convertView = layoutInflater.inflate(R.layout.list_item_family_data, parent, false);
            }

            FamilyData data = familyDataList.get(position);
            TextView family_data_name = (TextView) convertView.findViewById(R.id.family_data_name);
            family_data_name.setText(data.getUser_name());
            TextView family_data_heart = (TextView) convertView.findViewById(R.id.family_data_heart);
            family_data_heart.setText(data.getHeart());
            TextView family_data_loc = (TextView) convertView.findViewById(R.id.family_data_loc);
//            family_data_loc.setText(data.getLng() + " , " + data.getLat());
            family_data_loc.setText("桃園市中壢區中大路300號 (121.194929 , 24.971242)");

            return convertView;
        }
    }
}
