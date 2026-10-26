import 'dart:typed_data';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'family_form_repository.dart';

/// The shared dynamic editor receives only one authorized Health category.
class HealthProfileAdapter implements ProfileRepository, ProfileFileRepository {
  HealthProfileAdapter(
    this.repository,
    this.child, {
    required this.active,
    required this.onDenied,
  });
  final FamilyFormRepository repository;
  final FamilyFormChoice child;
  final bool Function() active;
  final void Function() onDenied;
  void _subject(ProfileSubject subject, String? type) {
    if (!active() ||
        !repository.isActive ||
        subject.personId != child.personId ||
        subject.schoolId != repository.schoolId ||
        (type ?? subject.profileType) != 'learner') {
      throw ApiException(
        'Your family access changed. Reopen this screen.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  Future<T> _perform<T>(Future<T> Function() call) async {
    try {
      return await call();
    } catch (error) {
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        onDenied();
      }
      rethrow;
    }
  }

  @override
  Future<ProfileForm?> fetch(ProfileSubject subject, {String? profileType}) =>
      _perform(() async {
        _subject(subject, profileType);
        final form = await repository.health(child.id, child.personId);
        _subject(subject, profileType);
        return form;
      });
  @override
  Future<void> save(
    ProfileSubject subject,
    String profileType,
    Map<String, ProfileValue> values,
  ) => _perform(() async {
    _subject(subject, profileType);
    await repository.saveHealth(child.id, child.personId, values);
    _subject(subject, profileType);
  });
  @override
  Future<void> upload(
    ProfileSubject subject,
    String profileType,
    String field,
    ProfileUpload file,
  ) => _perform(() async {
    _subject(subject, profileType);
    try {
      await repository.uploadHealth(
        child.id,
        child.personId,
        field,
        file.name,
        file.bytes,
      );
      _subject(subject, profileType);
    } finally {
      file.bytes.fillRange(0, file.bytes.length, 0);
    }
  });
  @override
  Future<Uint8List> readFile(ProfileSubject subject, ProfileAttachment file) =>
      _perform(() async {
        _subject(subject, 'learner');
        final bytes = await repository.healthFile(
          child.id,
          child.personId,
          file,
        );
        try {
          _subject(subject, 'learner');
          return bytes;
        } catch (_) {
          bytes.fillRange(0, bytes.length, 0);
          rethrow;
        }
      });
}
