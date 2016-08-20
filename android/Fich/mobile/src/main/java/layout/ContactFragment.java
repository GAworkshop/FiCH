package layout;

import android.content.Context;
import android.net.Uri;
import android.os.Bundle;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ExpandableListView;
import android.widget.TextView;

import com.example.user.fich.Contact;
import com.example.user.fich.PreferencesHelper;
import com.example.user.fich.R;

import java.util.ArrayList;

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

    private static final String contact = "contact";
    private static final int IMAGE_PICKER = 1;
    private int lastExp = -1;
    public View targetView;
    static final int PICK_CONTACT_REQUEST = 1;
    PreferencesHelper prefHelper;

    ArrayList<Contact> contact_list = new ArrayList<Contact>();
    Contact c1;
    Contact c2;
    Contact c3;

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
        //contact_list.add(new Contact(0, BitmapFactory.decodeResource(getResources(), R.drawable.head_me),"Ben", "123456789"));
        //contact_list.add(new Contact(1, BitmapFactory.decodeResource(getResources(), R.drawable.head_me),"Huang", "000000000"));
        //contact_list.add(new Contact(2, BitmapFactory.decodeResource(getResources(), R.drawable.head_me),"Huang", "111111111"));

        prefHelper = new PreferencesHelper(getActivity());

        return;
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        // Inflate the layout for this fragment
        Log.e("~~DEBUG~~", "Contact Fragment onCreateView");



        final View view = inflater.inflate(R.layout.fragment_contact, container, false);

        view.findViewById(R.id.btn_save).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {

                String name = ((TextView)view.findViewById(R.id.editText)).getText().toString();
                String phone = ((TextView)view.findViewById(R.id.editText2)).getText().toString();
                prefHelper.storeData("c_name", name);
                prefHelper.storeData("c_phone", phone);
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
        Log.e("~~DEBUG~~", "Contact Fragment onDEtach");
        //mListener = null;
    }

    @Override
    public void onDestroy(){
        super.onDestroy();
        Log.e("~~DEBUG~~", "Contact Fragment onDestroy");
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