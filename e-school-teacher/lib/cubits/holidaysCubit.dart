import 'package:eschool_teacher/data/models/holiday.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class HolidaysState {}

class HolidaysInitial extends HolidaysState {}

class HolidaysFetchInProgress extends HolidaysState {}

class HolidaysFetchSuccess extends HolidaysState {

  HolidaysFetchSuccess({required this.holidays});
  final List<Holiday> holidays;
}

class HolidaysFetchFailure extends HolidaysState {

  HolidaysFetchFailure(this.errorMessage);
  final String errorMessage;
}

class HolidaysCubit extends Cubit<HolidaysState> {

  HolidaysCubit(this._systemRepository) : super(HolidaysInitial());
  final SystemRepository _systemRepository;

  Future<void> fetchHolidays() async {
    emit(HolidaysFetchInProgress());
    try {
      emit(
        HolidaysFetchSuccess(
          holidays: await _systemRepository.fetchHolidays(),
        ),
      );
    } catch (e) {
      emit(HolidaysFetchFailure(e.toString()));
    }
  }

  List<Holiday> holidays() {
    if (state is HolidaysFetchSuccess) {
      return (state as HolidaysFetchSuccess).holidays;
    }
    return [];
  }
}
