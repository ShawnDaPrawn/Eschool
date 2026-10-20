import 'package:eschool_teacher/data/repositories/announcementRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class DeleteAnnouncementState {}

class DeleteAnnouncementInitial extends DeleteAnnouncementState {}

class DeleteAnnouncementInProgress extends DeleteAnnouncementState {}

class DeleteAnnouncementSuccess extends DeleteAnnouncementState {}

class DeleteAnnouncementFailure extends DeleteAnnouncementState {

  DeleteAnnouncementFailure(this.errorMessage);
  final String errorMessage;
}

class DeleteAnnouncementCubit extends Cubit<DeleteAnnouncementState> {

  DeleteAnnouncementCubit(this._announcementRepository)
      : super(DeleteAnnouncementInitial());
  final AnnouncementRepository _announcementRepository;

  Future<void> deleteAnnouncement(int announcementId) async {
    emit(DeleteAnnouncementInProgress());
    try {
      await _announcementRepository.deleteAnnouncement(announcementId);
      emit(DeleteAnnouncementSuccess());
    } catch (e) {
      emit(DeleteAnnouncementFailure(state.toString()));
    }
  }
}
