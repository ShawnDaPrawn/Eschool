import 'package:eschool/data/models/sliderDetails.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class SlidersState {}

class SlidersInitial extends SlidersState {}

class SlidersFetchInProgress extends SlidersState {}

class SlidersFetchSuccess extends SlidersState {

  SlidersFetchSuccess({required this.sliders});
  final List<SliderDetails> sliders;
}

class SlidersFetchFailure extends SlidersState {

  SlidersFetchFailure(this.errorMessage);
  final String errorMessage;
}

class SlidersCubit extends Cubit<SlidersState> {

  SlidersCubit(this._systemRepository) : super(SlidersInitial());
  final SystemRepository _systemRepository;

  Future<void> fetchSliders() async {
    emit(SlidersFetchInProgress());
    try {
      final sliders = await _systemRepository.fetchSliders();
      emit(SlidersFetchSuccess(sliders: sliders));
    } catch (e) {
      emit(SlidersFetchFailure(e.toString()));
    }
  }
}
