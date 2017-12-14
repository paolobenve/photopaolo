# original from https://gist.github.com/Markbnj/e1541d15699c4d2d8c98

import requests
import json
import Options
from CachePath import *
import TreeWalker
import math

# For information on endpoints and arguments see the geonames
# API documentation at:
#
#   http://www.geonames.org/export/web-services.html

class Geonames(object):
	"""
	This class provides a client to call certain entrypoints of the geonames
	API.
	"""
	GEONAMES_API = "http://api.geonames.org/"
	# through this cache many calls to geonames web services are saved
	geonames_cache = {}
	# the maximum distance in meters for considering two different coordinates equivalent
	max_distance_meters = 50

	def __init__(self):
		GEONAMES_USER = Options.config['geonames_user']
		GEONAMES_LANGUAGE = Options.config['geonames_language']
		self._base_feature_url = "{}getJSON?geonameId={{}}&username={}&style=full&lang={}".format(self.GEONAMES_API, GEONAMES_USER, GEONAMES_LANGUAGE)
		self._base_nearby_url = "{}findNearbyJSON?lat={{}}&lng={{}}{{}}&username={}&lang={}".format(self.GEONAMES_API, GEONAMES_USER, GEONAMES_LANGUAGE)
		self._base_neighbourhood_url = "{}neighbourhoodJSON?lat={{}}&lng={{}}&username={}&lang={}".format(self.GEONAMES_API, GEONAMES_USER, GEONAMES_LANGUAGE)

	def lookup_nearby_place(self, latitude, longitude, feature_class='P', feature_code=None):
		"""
		Looks up places near a specific geographic location, optionally
		filtering for feature class and feature code.
		"""

		for (c_latitude, c_longitude, c_feature_class, c_feature_code) in Geonames.geonames_cache:
			distance = self.distance_between_coordinates(c_latitude, c_longitude, latitude, longitude)
			if c_feature_class == feature_class and c_feature_code == feature_code and distance < self.max_distance_meters:
				# get it from cache!
				result = Geonames.geonames_cache[(c_latitude, c_longitude, feature_class, feature_code)]
				next_level()
				message("geoname got from cache", "", 5)
				back_level()
				# add to cache only if not too closed to existing point
				if distance > self.max_distance_meters / 10.0:
					Geonames.geonames_cache[(latitude, longitude, feature_class, feature_code)] = result
				return result

		feature_filter = ''
		if feature_class:
			feature_filter += "&featureClass={}".format(feature_class)
		if feature_code:
			feature_filter += "&featureCode={}".format(feature_code)

		# get country, region (state for federal countries), and place
		url = self._base_nearby_url.format(latitude, longitude, feature_filter)
		response = requests.get(url)
		result = self._decode_nearby_place(response.text)
		next_level()
		message("geoname got from geonames.org", "", 5)
		back_level()
		# I had an idea of running another request in order to get the nearest city with population of 15000+ and use it as an intermediate level between region and places
		# I'm not sure it's a good thing...
		# url = self._base_nearby_city_url.format(latitude, longitude, feature_filter)
		# print "looking up city", url
		# response = requests.get(url)
		# result_city = self._decode_nearby_place(response.text)
		# result['city_name'] = result_city['place_name']
		# result['city_code'] = result_city['place_code']

		# add to cache
		Geonames.geonames_cache[(latitude, longitude, feature_class, feature_code)] = result

		return result

	def _decode_nearby_place(self, response_text):
		"""
		Decodes the response from the geonames nearby place lookup and
		returns the properties in a dict.
		"""
		raw_result = json.loads(response_text)
		result = {}

		if 'status' not in raw_result and len(raw_result['geonames']) > 0:
				geoname = raw_result['geonames'][0]
				correspondence = {
					'country_name': 'countryName',
					'country_code': 'countryCode',
					'region_name': 'adminName1',
					'region_code': 'adminCode1',
					'place_name': 'name',
					'place_code': 'geonameId',
					'latitude': 'lat',
					'longitude': 'lng',
					'distance': 'distance'
				}
				for index in correspondence:
					# vatican places don't have region fields, and perhaps others fields could not exist
					if correspondence[index] in geoname:
						result[index] = geoname[correspondence[index]]
					else:
						if index[-5:] == '_code':
							result[index] = Options.config['unspecified_geonames_code']
						else:
							result[index] = ''
		return result

	# a recursive function that receives a big list of photos whose coordinates are quite near each other
	# and returns a list of smaller clusters not farther than max_distance
	def reduce_clusters_size(self, media_list, max_distance):
		next_level()
		message("big list found, " + str(len(media_list)) + " photos", "reducing with max_distance=" + str(max_distance), 5)
		cluster_list = []
		biggest_cluster_size = 0
		for media in media_list:
			found = False
			for i, cluster in enumerate(cluster_list):
				if self.distance_between_coordinates(media.latitude, media.longitude, cluster['center']['latitude'], cluster['center']['longitude']) < max_distance:
					cluster_list[i]['center']['latitude'] = self.recalculate_mean(cluster['center']['latitude'], len(cluster['media_list']), media.latitude)
					cluster_list[i]['center']['longitude'] = self.recalculate_mean(cluster['center']['longitude'], len(cluster['media_list']), media.longitude)
					cluster_list[i]['media_list'].append(media)
					if len(cluster['media_list']) > biggest_cluster_size:
						biggest_cluster_size = len(cluster['media_list'])
					found = True
					break
			if not found:
				new_cluster = {}
				new_cluster['center'] = {'latitude': media.latitude, 'longitude': media.longitude}
				new_cluster['media_list'] = []
				new_cluster['media_list'].append(media)
				if biggest_cluster_size == 0:
					biggest_cluster_size = 1
				cluster_list.append(new_cluster)

		reorganized_cluster_list = []
		for i, cluster in enumerate(cluster_list):
			if len(cluster['media_list']) > Options.config['big_virtual_folders_threshold']:
				if max_distance > 1:
					reorganized_cluster_list.extend(self.reduce_clusters_size(cluster['media_list'], max_distance / 2))
				else:
					last = Options.config['big_virtual_folders_threshold'] - 1
					reorganized_cluster_list.append(cluster['media_list'][0:last])
					reorganized_cluster_list.extend(self.reduce_clusters_size(cluster['media_list'][Options.config['big_virtual_folders_threshold']:], max_distance / 2))
			else:
				reorganized_cluster_list.append(cluster['media_list'])

		biggest_cluster_size = 0
		for cluster in reorganized_cluster_list:
			length = len(cluster)
			if length > biggest_cluster_size:
				biggest_cluster_size = length
		next_level()
		message("reduced to " + str(len(reorganized_cluster_list)), "biggest size is " + str(biggest_cluster_size), 5)
		back_level()
		back_level()
		return reorganized_cluster_list

	def recalculate_mean(self, old_mean, old_len, new_value, new_len = 1):
		return (old_mean * old_len + new_value * new_len) / (old_len + new_len)


	def distance_between_media(self, media1, media2):
		# calculate the distance between the media gps coordinates
		lat1 = media1.latitude
		lon1 = media1.longitude
		lat2 = media2.latitude
		lon2 = media2.longitude

		return self.distance_between_coordinates(lat1, lon1, lat2, lon2)

	def distance_between_coordinates(self, lat1, lon1, lat2, lon2):
		# https://gis.stackexchange.com/questions/61924/python-gdal-degrees-to-meters-without-reprojecting
		# Calculate the great circle distance in meters between two points on the earth (specified in decimal degrees)

		# convert decimal degrees to radians
		r_lon1, r_lat1, r_lon2, r_lat2 = map(math.radians, [lon1, lat1, lon2, lat2])
		# haversine formula
		d_r_lon = r_lon2 - r_lon1
		d_r_lat = r_lat2 - r_lat1
		a = math.sin(d_r_lat / 2.0) ** 2 + math.cos(r_lat1) * math.cos(r_lat2) * math.sin(d_r_lon / 2.0) ** 2
		c = 2.0 * math.asin(math.sqrt(a))
		m = 6371.0 * c * 1000.0
		return m

	def lookup_feature(self, geoname_id):
		"""
		Looks up a feature based on its geonames id
		"""
		url = self._base_feature_url.format(geoname_id)
		response = requests.get(url)
		return self._decode_feature(response.text)

	def _decode_feature(self, response_text):
		"""
		Decodes the response from geonames.org feature lookup and
		returns the properties in a dict.
		"""
		raw_result = json.loads(response_text)

		if 'status' in raw_result:
				raise Exception("Geonames: call returned status {}".format(raw_result['status']['value']))

		result = dict(
				geoname_id=raw_result['geonameId'],
				name=raw_result['name'],
				country_name=raw_result['countryName'],
				country_code=raw_result['countryCode'],
				region_name=raw_result['adminName1'],
				region_code=raw_result['adminCode1'],
				toponym_name=raw_result['toponymName']
		)
		return result
