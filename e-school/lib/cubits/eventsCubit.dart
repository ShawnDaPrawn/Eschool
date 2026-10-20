import 'package:equatable/equatable.dart';
import 'package:eschool/data/models/event.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class EventsState extends Equatable {}

class EventsInitial extends EventsState {
  @override
  List<Object?> get props => [];
}

class EventsFetchInProgress extends EventsState {
  @override
  List<Object?> get props => [];
}

class EventsFetchSuccess extends EventsState {

  EventsFetchSuccess({required this.events});
  final List<Event> events;
  @override
  List<Object?> get props => [events];
}

class EventsFetchFailure extends EventsState {

  EventsFetchFailure(this.errorMessage);
  final String errorMessage;

  @override
  List<Object?> get props => [errorMessage];
}

class EventsCubit extends Cubit<EventsState> {

  EventsCubit(this._systemRepository) : super(EventsInitial());
  final SystemRepository _systemRepository;

  Future<void> fetchEvents() async {
    emit(EventsFetchInProgress());
    try {
      emit(
        EventsFetchSuccess(
          events: await _systemRepository.fetchEvents(),
        ),
      );
    } catch (e) {
      emit(EventsFetchFailure(e.toString()));
    }
  }

  List<Event> events() {
    if (state is EventsFetchSuccess) {
      return (state as EventsFetchSuccess).events;
    }
    return [];
  }
}
