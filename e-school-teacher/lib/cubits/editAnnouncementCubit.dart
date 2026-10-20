import 'package:eschool_teacher/data/repositories/announcementRepository.dart';
import 'package:eschool_teacher/utils/api.dart';

import 'package:file_picker/file_picker.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class EditAnnouncementState {}

class EditAnnouncementInitial extends EditAnnouncementState {}

class EditAnnouncementInProgress extends EditAnnouncementState {}

class EditAnnouncementSuccess extends EditAnnouncementState {}

class EditAnnouncementFailure extends EditAnnouncementState {
  EditAnnouncementFailure(this.exception);
  final ApiException exception;
}

class EditAnnouncementCubit extends Cubit<EditAnnouncementState> {
  EditAnnouncementCubit(this._announcementRepository)
    : super(EditAnnouncementInitial());
  final AnnouncementRepository _announcementRepository;

  Future<void> editAnnouncement({
    required String title,
    required String description,
    required List<PlatformFile> attachments,
    required int classSectionId,
    required int subjectId,
    String? semesterId,
    required int announcementId,
  }) async {
    emit(EditAnnouncementInProgress());
    try {
      await _announcementRepository.updateAnnouncement(
        title: title,
        description: description,
        attachments: attachments,
        classSectionId: classSectionId,
        subjectId: subjectId,
        semesterId: semesterId,
        announcementId: announcementId,
      );
      emit(EditAnnouncementSuccess());
    } catch (e) {
      emit(EditAnnouncementFailure(ApiException.fromException(e)));
    }
  }
}
