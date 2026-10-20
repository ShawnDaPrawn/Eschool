import 'package:eschool_teacher/data/models/eventSchedule.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class EventDetailsState {}

class EventDetailsInitial extends EventDetailsState {}

class EventDetailsFetchInProgress extends EventDetailsState {}

class EventDetailsFetchSuccess extends EventDetailsState {

  EventDetailsFetchSuccess({
    required this.eventDetails,
    required this.sortedDates,
  });
  final List<EventSchedule> eventDetails;
  final List<DateTime> sortedDates;
}

class EventDetailsFetchFailure extends EventDetailsState {

  EventDetailsFetchFailure(this.errorMessage);
  final String errorMessage;
}

class EventDetailsCubit extends Cubit<EventDetailsState> {

  EventDetailsCubit(this._systemRepository) : super(EventDetailsInitial());
  final SystemRepository _systemRepository;

  Future<void> fetchEventDetails({required String eventId}) async {
    emit(EventDetailsFetchInProgress());
    try {
      final List<EventSchedule> schedules =
          await _systemRepository.fetchEventDetails(eventId: eventId);
      final List<DateTime> days =
          schedules.map<DateTime>((e) => e.date).toList();

      // Sorting the list of dates in ascending order
      days.sort((a, b) => a.compareTo(b));

      // Removing duplicate dates
      final List<DateTime> uniqueDates = days.toSet().toList();

      emit(
        EventDetailsFetchSuccess(
          eventDetails: schedules,
          sortedDates: uniqueDates,
        ),
      );
    } catch (e) {
      emit(EventDetailsFetchFailure(e.toString()));
    }
  }
}
