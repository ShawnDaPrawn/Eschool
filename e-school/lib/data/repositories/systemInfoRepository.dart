import 'dart:convert';

import 'package:eschool/data/models/event.dart';
import 'package:eschool/data/models/eventSchedule.dart';
import 'package:eschool/data/models/galleryAlbum.dart';
import 'package:eschool/data/models/holiday.dart';
import 'package:eschool/data/models/semester.dart';
import 'package:eschool/data/models/sessionYear.dart';
import 'package:eschool/data/models/sliderDetails.dart';
import 'package:eschool/utils/api.dart';
import 'package:flutter/foundation.dart';

class SystemRepository {
  Future<List<SliderDetails>> fetchSliders() async {
    try {
      final result = await Api.get(url: Api.getSliders, useAuthToken: false);
      return (result['data'] as List)
          .map((sliderDetails) => SliderDetails.fromJson(sliderDetails))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<dynamic> fetchSettings({required String type}) async {
    try {
      final result = await Api.get(
        queryParameters: {'type': type},
        url: Api.settings,
        useAuthToken: false,
      );
      return result['data'];
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<List<Holiday>> fetchHolidays() async {
    try {
      final result = await Api.get(url: Api.holidays, useAuthToken: false);
      return ((result['data'] ?? []) as List)
          .map((holiday) => Holiday.fromJson(Map.from(holiday)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<List<Event>> fetchEvents() async {
    try {
      final result = await Api.get(url: Api.events, useAuthToken: true);
      return ((result['data'] ?? []) as List)
          .map((event) => Event.fromJson(Map.from(event)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<List<EventSchedule>> fetchEventDetails({
    required String eventId,
  }) async {
    try {
      final result = await Api.get(
        url: Api.eventDetails,
        useAuthToken: true,
        queryParameters: {'event_id': eventId},
      );
      return ((result['data'] ?? []) as List)
          .map((event) => EventSchedule.fromJson(Map.from(event)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<Uint8List> downloadAcademicCalendarPDF({String? childId}) async {
    final result = await Api.get(
      url: childId != null
          ? Api.getParentAcademicCalendarPDF
          : Api.getStudentAcademicCalendarPDF,
      useAuthToken: true,
      queryParameters: childId != null ? {'child_id': childId} : null,
    );
    return base64Decode(result['pdf']);
  }

  Future<Uint8List> downloadIdCardPDF({String? childId}) async {
    final result = await Api.get(
      url: childId != null ? Api.getParentIdCardPDF : Api.getStudentIdCardPDF,
      useAuthToken: true,
      queryParameters: childId != null ? {'child_id': childId} : null,
    );
    return base64Decode(result['pdf']);
  }

  Future<({List<GalleryAlbum> albums, int currentPage, int totalPages})>
  fetchGalleryAlbums({required int page}) async {
    try {
      final result = await Api.get(
        url: Api.getGalleryAlbums,
        queryParameters: {'page': page},
        useAuthToken: false,
      );

      return (
        albums: ((result['data']['data'] ?? []) as List)
            .map<GalleryAlbum>((item) => GalleryAlbum.fromJson(Map.from(item)))
            .toList(),
        currentPage:
            int.tryParse(result['data']['current_page']?.toString() ?? '0') ??
            0,
        totalPages:
            int.tryParse(result['data']['last_page']?.toString() ?? '0') ?? 0,
      );
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<List<String>> fetchGalleryAlbumPhotos({
    required String albumId,
  }) async {
    try {
      final result = await Api.get(
        url: Api.getGalleryImages,
        useAuthToken: false,
        queryParameters: {'album_id': albumId},
      );
      return ((result['data'] ?? []) as List)
          .map((item) => item.toString())
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<({List<String> videos, int currentPage, int totalPages})>
  fetchGalleryVideos({required int page}) async {
    try {
      final result = await Api.get(
        url: Api.getGalleryVideos,
        queryParameters: {'page': page},
        useAuthToken: false,
      );

      return (
        videos: ((result['data']['data'] ?? []) as List)
            .map((item) => item.toString())
            .toList(),
        currentPage:
            int.tryParse(result['data']['current_page']?.toString() ?? '0') ??
            0,
        totalPages:
            int.tryParse(result['data']['last_page']?.toString() ?? '0') ?? 0,
      );
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<List<Semester>> fetchSemesters() async {
    try {
      final result = await Api.get(url: Api.semesters, useAuthToken: true);

      return ((result['data'] ?? []) as List)
          .map((item) => Semester.fromJson(Map.from(item)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<List<SessionYear>> fetchSessionYears() async {
    try {
      final result = await Api.get(url: Api.sessionYears, useAuthToken: true);
      return ((result['data'] ?? []) as List)
          .map((event) => SessionYear.fromJson(Map.from(event)))
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }
}
