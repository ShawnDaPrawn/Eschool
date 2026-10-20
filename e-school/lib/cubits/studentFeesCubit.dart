// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
import 'package:eschool/data/models/paidFees.dart';
import 'package:eschool/data/repositories/studentRepository.dart';

abstract class StudentFeesState {}

class StudentFeesInitial extends StudentFeesState {}

class StudentFeesFetchSuccess extends StudentFeesState {

  StudentFeesFetchSuccess({required this.feesList});
  final List<PaidFees> feesList;
}

class StudentFeesFetchFailure extends StudentFeesState {

  StudentFeesFetchFailure(this.errorMessage);
  final String errorMessage;
}

class StudentFeesFetchInProgress extends StudentFeesState {}

class StudentFeesCubit extends Cubit<StudentFeesState> {

  StudentFeesCubit(this._studentRepository) : super(StudentFeesInitial());
  final StudentRepository _studentRepository;

  void fetchStudentFeesList({required int currentSessionYearId, int? childId}) {
    emit(StudentFeesFetchInProgress());
    _studentRepository.fetchFeesList(childId: childId ?? 0).then((value) {
      final List<PaidFees> tempList = value;
      if (tempList.length > 1) {
        if (tempList[0].sessionYearId != currentSessionYearId) {
          for (int i = 1; i < tempList.length; i++) {
            //making current session year fees first (if exixts)
            if (tempList[i].sessionYearId == currentSessionYearId) {
              tempList.insert(0, value[i]);
              tempList.removeAt(i + 1);
              break;
            }
          }
        }
      }
      return emit(StudentFeesFetchSuccess(feesList: tempList));
    }).catchError((e) => emit(StudentFeesFetchFailure(e.toString())));
  }
}
