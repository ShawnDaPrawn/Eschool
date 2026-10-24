import 'package:eschool/data/models/term.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class TermState {}

class TermFetchInitial extends TermState {}

class TermFetchLoading extends TermState {}

class TermFetchSuccess extends TermState {
  TermFetchSuccess({required this.terms});
  final List<Term> terms;
}

class TermFetchFailure extends TermState {
  TermFetchFailure(this.errorMessage);
  final String errorMessage;
}

class TermsCubit extends Cubit<TermState> {
  TermsCubit() : super(TermFetchInitial());

  void fetchTerms() async {
    emit(TermFetchLoading());
    try {
      final terms = await SystemRepository().fetchTerms();
      emit(TermFetchSuccess(terms: terms));
    } catch (e) {
      emit(TermFetchFailure(e.toString()));
    }
  }
}
