package layout;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.net.Uri;
import android.os.Bundle;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseExpandableListAdapter;
import android.widget.ExpandableListView;
import android.widget.TextView;

import com.example.user.fich.R;

import java.io.InputStream;

/**
 * A simple {@link Fragment} subclass.
 * Activities that contain this fragment must implement the
 * {@link ContactFragment.OnFragmentInteractionListener} interface
 * to handle interaction events.
 * Use the {@link ContactFragment#newInstance} factory method to
 * create an instance of this fragment.
 */
public class ContactFragment extends Fragment {

    //private OnFragmentInteractionListener mListener;
    private ExpandableListView expListView;

    SharedPreferences pref;
    SharedPreferences.Editor prefEdit;
    private static final String contact = "contact";
    private static final int IMAGE_PICKER = 1;

    public ContactFragment() {
        // Required empty public constructor
    }


    public static ContactFragment newInstance(String param1, String param2) {
        Log.e("~~DEBUG~~", "Contact Fragment newInstance");
        ContactFragment fragment = new ContactFragment();
        Bundle args = new Bundle();
//        args.putString(ARG_PARAM1, param1);
//        args.putString(ARG_PARAM2, param2);
        fragment.setArguments(args);
        return fragment;
    }

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        Log.e("~~DEBUG~~", "Contact Fragment onCreate");
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        // Inflate the layout for this fragment
        Log.e("~~DEBUG~~", "Contact Fragment onCreateView");
        View view = inflater.inflate(R.layout.fragment_contact, container, false);
        expListView = (ExpandableListView) view.findViewById(R.id.expandableListView);

        pref = getActivity().getApplication().getSharedPreferences(contact, Context.MODE_PRIVATE);
        prefEdit = pref.edit();

        expListView.setAdapter(new ExpListAdapter());
        expListView.setGroupIndicator(null);


        expListView.setOnGroupExpandListener(new ExpandableListView.OnGroupExpandListener() {
            private int lastExp = -1;

            @Override
            public void onGroupExpand(int groupPosition) {
                if(groupPosition != lastExp){
                    expListView.collapseGroup(lastExp);
                }
                lastExp = groupPosition;
            }
        });

        expListView.setOnChildClickListener(new ExpandableListView.OnChildClickListener() {
            @Override
            public boolean onChildClick(ExpandableListView parent, View v, int groupPosition, int childPosition, long id) {
                Log.e("C", "CHILD");
                return false;
            }
        });

        return view;
    }

    @Override
    public void onAttach(Context context) {
        super.onAttach(context);
        Log.e("~~DEBUG~~", "Contact Fragment onAttach");
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

    private class ExpListAdapter extends BaseExpandableListAdapter {

        private final LayoutInflater layoutInflater;

        public ExpListAdapter() {
            layoutInflater = LayoutInflater.from(getActivity());
        }

        @Override
        public int getGroupCount() {
            return 2;
        }

        @Override
        public int getChildrenCount(int groupPosition) {
            return 1;
        }

        @Override
        public Object getGroup(int groupPosition) {
            return "GROUP";
        }

        @Override
        public Object getChild(int groupPosition, int childPosition) {
            return "CHILD";
        }

        @Override
        public long getGroupId(int groupPosition) {
            return groupPosition;
        }

        @Override
        public long getChildId(int groupPosition, int childPosition) {
            return childPosition;
        }

        @Override
        public boolean hasStableIds() {
            return true;
        }

        @Override
        public View getGroupView(int groupPosition, boolean isExpanded, View convertView, ViewGroup parent) {

            if (convertView == null) {
                convertView = layoutInflater.inflate(R.layout.list_item_contact, parent, false);

            } else {

            }
            //((TextView)convertView.findViewById(R.id.textView9)).setText("123");

            return convertView;
        }

        @Override
        public View getChildView(int groupPosition, int childPosition, boolean isLastChild, View convertView, ViewGroup parent) {
            Log.e("", "RRRRRRRRRRRRRRRRRRRR");
            if (convertView == null) {
                convertView = layoutInflater.inflate(R.layout.list_expanded_contact, parent, false);

            } else {
                Log.e("", "QQQQQQQQQQQQQQQQQQQQQ");
            }
            ((TextView)convertView.findViewById(R.id.tv_phone)).setText("GGGGGGGGGGGG");

            return convertView;
        }

        @Override
        public boolean isChildSelectable(int groupPosition, int childPosition) {
            return false;
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

    private class clickListener implements View.OnClickListener{

        @Override
        public void onClick(View v) {
            Intent intent = new Intent();
            intent.setType("image/*");
            intent.setAction(Intent.ACTION_GET_CONTENT);
            startActivityForResult(intent, IMAGE_PICKER);
        }
    }

    @Override
    public void onActivityResult(int requestCode, int resultCode, Intent data){
        if(requestCode == IMAGE_PICKER && resultCode == Activity.RESULT_OK){
            try {
                InputStream in = getActivity().getContentResolver().openInputStream(data.getData());
                Bitmap img = BitmapFactory.decodeStream(in);
                in.close();

                //imageview.setImageBitmap(img);
            }catch (Exception e){

            }
        }
    }

}
