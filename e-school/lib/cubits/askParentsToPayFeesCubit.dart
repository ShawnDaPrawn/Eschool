// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class AskParentsToPayFeesState {}

class AskParentsToPayFeesInitial extends AskParentsToPayFeesState {}

class AskParentsToPayFeesFailed extends AskParentsToPayFeesState {
  ApiException exception;
  AskParentsToPayFeesFailed({required this.exception});
}

class AskParentsToPayFeesInProgress extends AskParentsToPayFeesState {}

class AskParentsToPayFeesSuccessfully extends AskParentsToPayFeesState {}

class AskParentsToPayFeesCubit extends Cubit<AskParentsToPayFeesState> {
  final StudentRepository _studentRepository = StudentRepository();
  AskParentsToPayFeesCubit() : super(AskParentsToPayFeesInitial());

  Future<void> askParentOrGuardianToPayFeesKey() async {
    emit(AskParentsToPayFeesInProgress());
    try {
      await _studentRepository.askParentsToPayFees();
      emit(AskParentsToPayFeesSuccessfully());
    } catch (e) {
      emit(AskParentsToPayFeesFailed(exception: ApiException.fromException(e)));
    }
  }
}
