import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/profile/profile_repository.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

enum ProfileStatus { loading, loaded, unavailable, failure }

class ProfileState extends Equatable {
  const ProfileState({
    this.status = ProfileStatus.loading,
    this.form,
    this.subject = const ProfileSubject(),
    this.draft = const {},
    this.busy = false,
    this.error,
    this.revision = 0,
    this.saved = false,
  });
  final ProfileStatus status;
  final ProfileForm? form;
  final ProfileSubject subject;
  final Map<String, ProfileValue> draft;
  final bool busy;
  final ApiException? error;
  final int revision;
  final bool saved;
  @override
  List<Object?> get props => [
    status,
    form,
    subject,
    draft,
    busy,
    error,
    revision,
    saved,
  ];
}

/// Drafts live only in this screen's memory, never in context/Hive storage.
class ProfileCubit extends Cubit<ProfileState> {
  ProfileCubit(this.repository) : super(const ProfileState());
  final ProfileRepository repository;
  int generation = 0;
  String? _requestedType;
  ApiException _error(Object error) => error is ApiException
      ? error
      : ApiException(ApiErrorCodes.defaultErrorMessageKey);

  Future<void> load(ProfileSubject subject, {String? profileType}) async {
    _requestedType = profileType ?? subject.profileType;
    final request = ++generation;
    emit(ProfileState(subject: subject, revision: request));
    try {
      final form = await repository.fetch(subject, profileType: profileType);
      if (!isClosed && generation == request) {
        emit(
          ProfileState(
            status: form == null
                ? ProfileStatus.unavailable
                : ProfileStatus.loaded,
            subject: subject,
            form: form,
            revision: request,
          ),
        );
      }
    } catch (error) {
      if (!isClosed && generation == request) {
        emit(
          ProfileState(
            status: ProfileStatus.failure,
            subject: subject,
            error: _error(error),
            revision: request,
          ),
        );
      }
    }
  }

  Future<void> reload() => load(state.subject, profileType: _requestedType);

  void change(ProfileFormField field, ProfileValue value) {
    if (state.busy || state.status != ProfileStatus.loaded || !field.editable) {
      return;
    }
    emit(
      ProfileState(
        status: state.status,
        subject: state.subject,
        form: state.form,
        draft: {...state.draft, field.key: value},
        revision: state.revision,
      ),
    );
  }

  void reset(ProfileFormField field) {
    if (state.busy || !field.editable) return;
    emit(
      ProfileState(
        status: state.status,
        subject: state.subject,
        form: state.form,
        draft: Map.of(state.draft)..remove(field.key),
        revision: state.revision,
      ),
    );
  }

  Future<bool> upload(
    ProfileFormField field,
    ProfileUpload file, {
    required ProfileSubject subject,
    required int revision,
  }) async {
    if (isClosed ||
        state.busy ||
        state.form == null ||
        !field.editable ||
        state.subject != subject ||
        state.revision != revision ||
        repository is! ProfileFileRepository) {
      return false;
    }
    final kept = state;
    final request = generation;
    emit(
      ProfileState(
        status: kept.status,
        subject: kept.subject,
        form: kept.form,
        draft: kept.draft,
        revision: kept.revision,
        busy: true,
      ),
    );
    bool acknowledged = false;
    try {
      await (repository as ProfileFileRepository).upload(
        subject,
        kept.form!.profileType,
        field.key,
        file,
      );
      acknowledged = true;
      final form = await repository.fetch(
        subject,
        profileType: kept.form!.profileType,
      );
      if (isClosed || request != generation) return false;
      emit(
        ProfileState(
          status: form == null
              ? ProfileStatus.unavailable
              : ProfileStatus.loaded,
          subject: subject,
          form: form,
          draft: Map.of(kept.draft)..remove(field.key),
          revision: revision,
          saved: true,
        ),
      );
      return true;
    } catch (error) {
      if (!isClosed && request == generation) {
        // A successful replacement invalidates the old link, even if refreshing fails.
        final json = kept.form!.toJson();
        if (acknowledged) {
          for (final category in json['categories'] as List) {
            for (final item in category['fields'] as List) {
              if (item['key'] == field.key) item['value'] = null;
            }
          }
        }
        emit(
          ProfileState(
            status: kept.status,
            subject: subject,
            form: ProfileForm.fromJson(json),
            draft: acknowledged
                ? (Map.of(kept.draft)..remove(field.key))
                : kept.draft,
            revision: revision,
            error: _error(error),
          ),
        );
      }
      return false;
    }
  }

  Future<bool> save() async {
    if (state.busy || state.form == null || state.draft.isEmpty) return false;
    final kept = state;
    final request = generation;
    emit(
      ProfileState(
        status: kept.status,
        subject: kept.subject,
        form: kept.form,
        draft: kept.draft,
        busy: true,
        revision: kept.revision,
      ),
    );
    try {
      await repository.save(kept.subject, kept.form!.profileType, kept.draft);
    } catch (error) {
      if (!isClosed && generation == request) {
        emit(
          ProfileState(
            status: kept.status,
            subject: kept.subject,
            form: kept.form,
            draft: kept.draft,
            error: _error(error),
            revision: kept.revision,
          ),
        );
      }
      return false;
    }
    if (isClosed || generation != request) return false;
    // An acknowledged write clears the private draft even if refreshing fails.
    await load(kept.subject, profileType: kept.form!.profileType);
    if (isClosed || generation != request + 1) return false;
    emit(
      ProfileState(
        status: state.status,
        subject: state.subject,
        form: state.form,
        error: state.error,
        revision: state.revision,
        saved: true,
      ),
    );
    return true;
  }
}
