import 'package:eschool_teacher/data/repositories/announcementRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class CreateAnnouncementState {}

class CreateAnnouncementInitial extends CreateAnnouncementState {}

class CreateAnnouncementInProgress extends CreateAnnouncementState {}

class CreateAnnouncementSuccess extends CreateAnnouncementState {}

class CreateAnnouncementFailure extends CreateAnnouncementState {
  CreateAnnouncementFailure(this.exception);
  final ApiException exception;
}

class CreateAnnouncementCubit extends Cubit<CreateAnnouncementState> {
  CreateAnnouncementCubit(this._announcementRepository)
    : super(CreateAnnouncementInitial());
  final AnnouncementRepository _announcementRepository;

  Future<void> createAnnouncement({
    required String title,
    required String description,
    required List<PlatformFile> attachments,
    required int classSectionId,
    required int subjectId,
    String? termId,
  }) async {
    emit(CreateAnnouncementInProgress());
    try {
      await _announcementRepository.createAnnouncement(
        title: title,
        description: description,
        attachments: attachments,
        classSectionId: classSectionId,
        subjectId: subjectId,
        termId: termId,
      );
      emit(CreateAnnouncementSuccess());
    } catch (e) {
      emit(CreateAnnouncementFailure(ApiException.fromException(e)));
    }
  }
}
