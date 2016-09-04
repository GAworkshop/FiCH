package layout;

import android.content.Context;
import android.net.Uri;
import android.os.Bundle;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import com.example.user.fich.PreferencesHelper;
import com.example.user.fich.R;

/**
 * A simple {@link Fragment} subclass.
 * Activities that contain this fragment must implement the
 * {@link CardFragment.OnFragmentInteractionListener} interface
 * to handle interaction events.
 * Use the {@link CardFragment#newInstance} factory method to
 * create an instance of this fragment.
 */
public class CardFragment extends Fragment {

    //private OnFragmentInteractionListener mListener;
    PreferencesHelper prefHelper;
    View view;

    public CardFragment() {
        // Required empty public constructor
    }


    public static CardFragment newInstance(String param1, String param2) {
        CardFragment fragment = new CardFragment();
        Bundle args = new Bundle();
//        args.putString(ARG_PARAM1, param1);
//        args.putString(ARG_PARAM2, param2);
        fragment.setArguments(args);
        return fragment;
    }

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        prefHelper = new PreferencesHelper(getActivity());
        if (getArguments() != null) {

        }
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        // Inflate the layout for this fragment
        Log.e("GG","GGGGGGGGG~~~~~~~~~~~~~~~~~~~");
        view = inflater.inflate(R.layout.fragment_card, container, false);

        ((TextView)view.findViewById(R.id.textView5)).setText(prefHelper.getString(getResources().getString(R.string.name)));
        ((TextView)view.findViewById(R.id.textView6)).setText(prefHelper.getString(getResources().getString(R.string.name)));
        ((TextView)view.findViewById(R.id.textView7)).setText(prefHelper.getString(getResources().getString(R.string.birthday)));
        ((TextView)view.findViewById(R.id.textView8)).setText(prefHelper.getString(getResources().getString(R.string.blood)));

        ((TextView)view.findViewById(R.id.tv_history)).setText(prefHelper.getString(getResources().getString(R.string.history)));
        ((TextView)view.findViewById(R.id.tv_allergic)).setText(prefHelper.getString(getResources().getString(R.string.allergic)));

        return view;
    }

    @Override
    public void onResume(){
        super.onResume();

        ((TextView)view.findViewById(R.id.textView5)).setText(prefHelper.getString(getResources().getString(R.string.name)));
        ((TextView)view.findViewById(R.id.textView6)).setText(prefHelper.getString(getResources().getString(R.string.name)));
        ((TextView)view.findViewById(R.id.textView7)).setText(prefHelper.getString(getResources().getString(R.string.birthday)));
        ((TextView)view.findViewById(R.id.textView8)).setText(prefHelper.getString(getResources().getString(R.string.blood)));

        ((TextView)view.findViewById(R.id.tv_history)).setText(prefHelper.getString(getResources().getString(R.string.history)));
        ((TextView)view.findViewById(R.id.tv_allergic)).setText(prefHelper.getString(getResources().getString(R.string.allergic)));
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
