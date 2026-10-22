import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:equatable/equatable.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/common/learner_summary.dart';

// PUT /api/v2/me/avatar (Phase 1 contract), plus GET /api/v2/me/avatar with
// the same answer for the current choice. Hand-written until openapi.yaml has
// them; then use the generated models and client.

/// The signed-in user's picture: an avatar of the set, or their own photo,
/// which the school may need to approve first.
class AvatarChoice extends Equatable {
  const AvatarChoice({
    required this.kind,
    required this.status,
    this.key,
    this.url,
  });

  factory AvatarChoice.fromJson(Map<String, dynamic> json) => AvatarChoice(
    kind: '${json['kind'] ?? set}',
    key: readString(json['key']),
    url: readString(json['url']),
    status: '${json['status'] ?? approved}',
  );

  /// [kind] of an avatar from the set.
  static const String set = 'set';

  /// [kind] of an uploaded photo.
  static const String photo = 'photo';
  static const String approved = 'approved';
  static const String pending = 'pending';

  final String kind;

  /// The avatar's key (`girl_2`) when [kind] is [set].
  final String? key;
  final String? url;
  final String status;

  bool get isPending => status == pending;

  @override
  List<Object?> get props => [kind, key, url, status];
}

/// A photo picked on the device, to upload.
class PickedPhoto {
  const PickedPhoto({required this.bytes, required this.fileName});

  final Uint8List bytes;
  final String fileName;
}

abstract interface class AvatarRepository {
  /// The current choice; `null` when the server has no avatars yet (404).
  Future<AvatarChoice?> current();

  /// Chooses the avatar [key] of the set.
  Future<AvatarChoice> choose(String key);

  /// Uploads [photo] as the user's picture.
  Future<AvatarChoice> upload(PickedPhoto photo);
}

class ApiAvatarRepository implements AvatarRepository {
  ApiAvatarRepository(this._client, {String? url})
    : url = url ?? '${AppConfig.apiV2Url}me/avatar';

  final ApiClient _client;
  final String url;

  @override
  Future<AvatarChoice?> current() async {
    final Map<String, dynamic>? result = await _client.send(
      method: 'GET',
      url: url,
      notFoundAsNull: true,
    );
    return result == null ? null : AvatarChoice.fromJson(dataMap(result));
  }

  @override
  Future<AvatarChoice> choose(String key) async {
    final Map<String, dynamic>? result = await _client.send(
      method: 'PUT',
      url: url,
      data: {'avatar_key': key},
    );
    return AvatarChoice.fromJson(dataMap(result ?? const {}));
  }

  /// PHP reads no multipart body on a PUT, so the photo goes as a POST that
  /// Laravel routes as the PUT (`_method`).
  @override
  Future<AvatarChoice> upload(PickedPhoto photo) async {
    final Map<String, dynamic>? result = await _client.send(
      method: 'POST',
      url: url,
      data: FormData.fromMap({
        '_method': 'PUT',
        'photo': MultipartFile.fromBytes(photo.bytes, filename: photo.fileName),
      }),
    );
    return AvatarChoice.fromJson(dataMap(result ?? const {}));
  }
}

/// The key of the profile picture screen in `EndpointAvailability`.
const String avatarFeature = 'avatar';

/// Whether the server has avatars yet (for `AvailableOnly`).
Future<bool> avatarsDeployed(AvatarRepository repository) async =>
    await repository.current() != null;
